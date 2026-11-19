<?php
// Offline only: no HTTP client, remote image loading or JavaScript execution.
function t9_text(DOMXPath $xpath, string $query, ?DOMNode $context = null): ?string
{
    $node = $xpath->query($query, $context)->item(0);
    $value = $node ? trim(preg_replace('/\s+/u', ' ', $node->textContent)) : '';
    return $value !== '' ? $value : null;
}
function t9_class(string $class): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' $class ')";
}
function t9_artist_slug(?string $url): ?string
{
    // Recognize artist profiles and their all-works lists, not individual artwork pages.
    return $url && preg_match('~\Ahttps://www\.wikiart\.org/en/([a-z0-9]+(?:-[a-z0-9]+)*)(?:/all-works(?:/[^?#]*)?)?/?(?:[?#].*)?\z~', $url, $match)
        ? $match[1] : null;
}
function t9_painting_slug(?string $url): ?string
{
    return $url && !t9_artist_slug($url)
        && preg_match('~\Ahttps://www\.wikiart\.org/en/([a-z0-9]+(?:-[a-z0-9]+)*)/[^/?#]+/?\z~', $url, $match)
        ? $match[1] : null;
}
function t9_source(string $href, string $slug): ?string
{
    if (str_starts_with($href, '/en/')) $href = 'https://www.wikiart.org' . $href;
    return preg_match('~^https://www\.wikiart\.org/en/' . preg_quote($slug, '~') . '/[^?#]+$~', $href) ? $href : null;
}
function t9_image(string $src, string $page, string $root): ?string
{
    if (preg_match('~^data:image/(?:jpeg|png|gif|webp);base64,(.*)$~s', $src, $m)) {
        $bytes = base64_decode($m[1], true);
        return $bytes === false ? null : $bytes;
    }
    // Resolve only relative files inside saved_pages. Remote and file:// URLs are rejected.
    if ($src === '' || preg_match('~^(?:[a-z][a-z0-9+.-]*:|//|/)~i', $src)) return null;
    $path = realpath(dirname($page) . '/' . rawurldecode(explode('?', $src)[0]));
    if (!$path || !str_starts_with($path, realpath($root) . DIRECTORY_SEPARATOR) || !is_file($path)) return null;
    return file_get_contents($path) ?: null;
}
function t9_parse(string $slug, string $label, string $input, string $images): array
{
    $artist = ['slug'=>$slug, 'requested_name'=>$label, 'name'=>null, 'source'=>'https://www.wikiart.org/en/'.$slug,
        'birth_date'=>null, 'death_date'=>null, 'nationality'=>null, 'biography'=>null, 'pages'=>[], 'paintings'=>[], 'issues'=>[]];
    $seen = []; $details = []; $descriptions = [];
    foreach (glob($input . '/*.html') ?: [] as $page) {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($page), LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $x = new DOMXPath($dom);
        $url = t9_text($x, '//meta[@property="og:url"]/@content') ?? t9_text($x, '//link[@rel="canonical"]/@href');
        if (t9_painting_slug($url) === $slug) {
            // Painting pages enrich matching works; their text is never an artist biography.
            $paragraphs=[];
            foreach ($x->query('//*[@itemprop="description"][not(self::meta) and not(ancestor::*[@itemprop="description"])]') as $paragraph) {
                $text=t9_text($x, '.', $paragraph);
                if ($text !== null) $paragraphs[]=$text;
            }
            if ($paragraphs) $descriptions[$url]=['text'=>implode("\n\n",$paragraphs), 'page'=>basename($page)];
            continue;
        }
        if (t9_artist_slug($url) !== $slug) continue;
        $artist['pages'][] = basename($page);
        // A detailed list can enrich paintings selected from any other saved page.
        foreach ($x->query('//*['.t9_class('wiki-detailed-item-container').']') as $entry) {
            $source=t9_source(t9_text($x,'.//a['.t9_class('masonry-detailed-artwork-title').']/@href',$entry) ?? '',$slug);
            if (!$source) continue;
            $info=['genres'=>[], 'description'=>t9_text($x,'.//*[@itemprop="description"]',$entry), 'detail_page'=>basename($page)];
            foreach ($x->query('.//*[@itemprop="genre"]',$entry) as $genre) {
                $value=trim($genre->textContent);
                if ($value!=='') $info['genres'][]=$value;
            }
            foreach (['medium'=>'Media:', 'dimensions'=>'Dimensions:', 'style'=>'Style:', 'original_title'=>'Original Title:'] as $key=>$heading) {
                $row=$x->query('.//li[s[normalize-space(.)="'.$heading.'"]]',$entry)->item(0);
                $info[$key]=$row ? trim(substr(trim(preg_replace('/\s+/u',' ',$row->textContent)),strlen($heading))) : null;
                if ($info[$key]==='') $info[$key]=null;
            }
            $info['date_text']=t9_text($x,'.//*[@itemprop="dateCreated"]',$entry);
            $details[$source]=$info;
        }
        $name = t9_text($x, '//*[@itemprop="name"][self::h1 or self::h2]')
            ?? t9_text($x, '//meta[@itemprop="name"]/@content')
            ?? t9_text($x, '//h1//a['.t9_class('artist-href').']')
            ?? t9_text($x, '//h1');
        if ($name) $artist['name'] = rtrim($name, ': ');
        foreach (['birth_date'=>'birthDate', 'death_date'=>'deathDate', 'nationality'=>'nationality'] as $key=>$prop) {
            $value = t9_text($x, '//*[@itemprop="'.$prop.'"]/@content')
                ?? t9_text($x, '//*[@itemprop="'.$prop.'"]/@datetime')
                ?? t9_text($x, '//*[@itemprop="'.$prop.'"]');
            if ($value) $artist[$key] = $value;
        }
        $bio = t9_text($x, '//*[@itemprop="description"][not(ancestor::*['.t9_class('wiki-detailed-item-container').'])]')
            ?? t9_text($x, '//*['.t9_class('wiki-biography').']');
        if (!$bio) {
            $paragraphs=[];
            foreach ($x->query('//*[@id="info-tab-wikipediaArticle"]//p') as $paragraph) {
                $paragraphs[]=trim(preg_replace('/\s+/u', ' ', $paragraph->textContent));
            }
            $bio=trim(implode("\n\n", $paragraphs)) ?: null;
        }
        if ($bio) $artist['biography'] = $bio;
        foreach ($x->query('//a['.t9_class('artwork-name').' or '.t9_class('masonry-detailed-artwork-title').']') as $link) {
            if (!($link instanceof DOMElement)) continue;
            $title = trim(preg_replace('/\s+/u', ' ', $link->textContent));
            $source = t9_source($link->getAttribute('href'), $slug);
            if (!$title || !$source || isset($seen[$source]) || count($artist['paintings']) >= 5) continue;
            $card = $x->query('ancestor::li[1]', $link)->item(0)
                ?? $x->query('ancestor::*['.t9_class('wiki-detailed-item-container').'][1]',$link)->item(0);
            if (!$card) continue;
            $bytes = null; $info = false;
            foreach ($x->query('.//img', $card) as $img) {
                if (!($img instanceof DOMElement)) continue;
                foreach (['src','data-src'] as $attr) {
                    $bytes = t9_image($img->getAttribute($attr), $page, $input);
                    $info = $bytes ? @getimagesizefromstring($bytes) : false;
                    if ($info && in_array($info['mime'], ['image/jpeg','image/png','image/gif','image/webp'], true)) break 2;
                    $info = false;
                }
            }
            if (!$info) { $artist['issues'][] = "Missing valid local image: $title ($source)"; continue; }
            $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$info['mime']];
            $stem = 't9_' . substr(hash('sha256', $source . $bytes), 0, 32);
            $dest = $images . '/' . $stem . '.' . $ext;
            if (file_exists($dest)) {
                if (hash_file('sha256', $dest) !== hash('sha256', $bytes)) throw new RuntimeException('Image collision: '.$dest);
            } else {
                $handle = fopen($dest, 'xb');
                if (!$handle) throw new RuntimeException('Cannot create image: '.$dest);
                $written = fwrite($handle, $bytes); fclose($handle);
                if ($written !== strlen($bytes)) throw new RuntimeException('Incomplete image write: '.$dest);
            }
            $yearText = t9_text($x, './/*['.t9_class('artwork-year').']', $card)
                ?? t9_text($x,'.//*[@itemprop="dateCreated"]',$card);
            $year = $yearText && preg_match('/\b([1-9][0-9]{3})\b/', $yearText, $m) ? (int)$m[1] : null;
            $artist['paintings'][] = ['title'=>$title,'year'=>$year,'year_text'=>$yearText,'source'=>$source,'image_stem'=>$stem,'extension'=>$ext,'saved_page'=>basename($page)];
            $seen[$source] = true;
        }
    }
    foreach ($artist['paintings'] as &$painting) {
        $painting=array_merge($painting,$details[$painting['source']] ?? ['genres'=>[], 'description'=>null, 'medium'=>null, 'dimensions'=>null, 'style'=>null, 'original_title'=>null, 'detail_page'=>null, 'date_text'=>null]);
        if (isset($descriptions[$painting['source']])) {
            $painting['description']=$descriptions[$painting['source']]['text'];
            $painting['description_page']=$descriptions[$painting['source']]['page'];
        }
        if (!$painting['year'] && $painting['date_text'] && preg_match('/\b([1-9][0-9]{3})\b/',$painting['date_text'],$m)) $painting['year']=(int)$m[1];
    }
    unset($painting);
    if (!$artist['pages']) $artist['issues'][] = 'Missing saved HTML for '.$slug;
    foreach (['name','birth_date','death_date','nationality','biography'] as $key) {
        if (!$artist[$key]) $artist['issues'][] = 'Not found in saved HTML: '.$key;
    }
    if (count($artist['paintings']) !== 5) $artist['issues'][] = 'Need five valid paintings; found '.count($artist['paintings']);
    return $artist;
}
// Hex literals avoid quote/backslash and SQL-mode escaping differences; preserve UTF-8.
function t9_sql_value(?string $value): string
{
    return $value === null ? 'NULL' : "CONVERT(X'".bin2hex($value)."' USING utf8mb4)";
}
function t9_year(?string $date): string
{
    return $date && preg_match('/\b([1-9][0-9]{3})\b/', $date, $m) ? $m[1] : 'NULL';
}
function t9_sql(array $artists): string
{
    $sql = "-- Task 9 offline import. Back up art first. Run as one file, one connection.\nSET NAMES utf8mb4;\nSTART TRANSACTION;\n";
    foreach ($artists as $a) {
        $parts = explode(' ', $a['name']); $last = array_pop($parts); $first = implode(' ', $parts);
        $f=t9_sql_value($first); $l=t9_sql_value($last); $url=t9_sql_value($a['source']);
        $match = "ArtistLink=$url OR (CONCAT_WS(' ',NULLIF(FirstName,''),LastName) COLLATE utf8mb4_unicode_ci = ".t9_sql_value($a['name'])." COLLATE utf8mb4_unicode_ci)";
        // Convert existing utf8 columns explicitly to allow either utf8 or utf8mb4 schemas.
        $match = str_replace("CONCAT_WS(' ',NULLIF(FirstName,''),LastName) COLLATE", "CONVERT(CONCAT_WS(' ',NULLIF(FirstName,''),LastName) USING utf8mb4) COLLATE", $match);
        $sql .= "INSERT INTO Artists (FirstName,LastName,Nationality,Gender,YearOfBirth,YearOfDeath,Details,ArtistLink) SELECT $f,$l,".t9_sql_value($a['nationality']).",NULL,".t9_year($a['birth_date']).','.t9_year($a['death_date']).','.t9_sql_value($a['biography']).",$url WHERE NOT EXISTS (SELECT 1 FROM Artists WHERE $match);\n";
        $sql .= "SET @task9_artist = (SELECT ArtistID FROM Artists WHERE $match ORDER BY ArtistID LIMIT 1);\n";
        // Fill gaps only. Existing nonempty artist information takes priority.
        $sql .= "UPDATE Artists SET Nationality=COALESCE(NULLIF(TRIM(Nationality),''),".t9_sql_value($a['nationality'])."), Details=COALESCE(NULLIF(TRIM(Details),''),".t9_sql_value($a['biography'])."), YearOfBirth=COALESCE(NULLIF(YearOfBirth,0),".t9_year($a['birth_date'])."), YearOfDeath=COALESCE(NULLIF(YearOfDeath,0),".t9_year($a['death_date']).") WHERE ArtistID=@task9_artist;\n";
        foreach ($a['paintings'] as $p) {
            $title=t9_sql_value($p['title']); $source=t9_sql_value($p['source']); $stem=t9_sql_value($p['image_stem']);
            $sql .= "INSERT INTO Paintings (ArtistID,ImageFileName,Title,YearOfWork,WikiLink,Medium,CopyrightText) SELECT @task9_artist,$stem,$title,".($p['year'] ?? 'NULL').",$source,NULL,NULL WHERE @task9_artist IS NOT NULL AND NOT EXISTS (SELECT 1 FROM Paintings WHERE ImageFileName=$stem OR (ArtistID=@task9_artist AND (WikiLink=$source OR Title=$title)));\n";
            $sql .= "SET @task9_painting=(SELECT PaintingID FROM Paintings WHERE ArtistID=@task9_artist AND (WikiLink=$source OR ImageFileName=$stem OR Title=$title) ORDER BY PaintingID LIMIT 1);\n";
            // Preserve exact dimensional text rather than guess axes or round into INTEGER columns.
            $description=[];
            foreach (['description'=>'Description', 'original_title'=>'Original title', 'style'=>'Style', 'dimensions'=>'Dimensions'] as $key=>$heading) {
                if (!empty($p[$key])) $description[]=$heading.': '.$p[$key];
            }
            $text=$description ? implode("\n",$description) : null;
            $sql .= "UPDATE Paintings SET Medium=COALESCE(NULLIF(TRIM(Medium),''),".t9_sql_value($p['medium'] ?? null)."), Description=COALESCE(NULLIF(TRIM(Description),''),".t9_sql_value($text)."), YearOfWork=COALESCE(NULLIF(YearOfWork,0),".($p['year'] ?? 'NULL').") WHERE PaintingID=@task9_painting;\n";
            foreach (array_unique($p['genres'] ?? []) as $genre) {
                $g=t9_sql_value($genre);
                $sql .= "INSERT INTO Genres (GenreName) SELECT $g WHERE NOT EXISTS (SELECT 1 FROM Genres WHERE GenreName=$g);\n";
                $sql .= "SET @task9_genre=(SELECT GenreID FROM Genres WHERE GenreName=$g ORDER BY GenreID LIMIT 1);\n";
                $sql .= "INSERT INTO PaintingGenres (PaintingID,GenreID) SELECT @task9_painting,@task9_genre WHERE @task9_painting IS NOT NULL AND NOT EXISTS (SELECT 1 FROM PaintingGenres WHERE PaintingID=@task9_painting AND GenreID=@task9_genre);\n";
            }
        }
    }
    return $sql."COMMIT;\n";
}
function t9_run(string $input, string $output, string $images): array
{
    if (!is_dir($images) || !is_writable($images)) throw new RuntimeException('Gallery image folder is not writable.');
    if (!is_dir($output) && !mkdir($output, 0775, true)) throw new RuntimeException('Cannot create output folder.');
    $slugs=[]; $paintingSlugs=[]; $issues=[];
    foreach (glob($input . '/*.html') ?: [] as $page) {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($page), LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $x = new DOMXPath($dom);
        $url = t9_text($x, '//meta[@property="og:url"]/@content') ?? t9_text($x, '//link[@rel="canonical"]/@href');
        $slug = t9_artist_slug($url);
        if ($slug) $slugs[$slug]=true;
        elseif ($paintingSlug=t9_painting_slug($url)) $paintingSlugs[$paintingSlug]=true;
        else $issues[]='Skipped '.basename($page).': no supported WikiArt artist or painting URL.';
    }
    foreach ($paintingSlugs as $slug=>$unused) {
        if (!isset($slugs[$slug])) $issues[]='Painting details for '.$slug.' need a saved artist profile or all-works page too.';
    }
    ksort($slugs);
    $artists=[];
    foreach ($slugs as $slug=>$unused) {
        $artist=t9_parse($slug,$slug,$input,$images);
        $artist['requested_name']=$artist['name'] ?? $slug;
        $artists[]=$artist;
    }
    if (!$artists) $issues[]='No saved WikiArt artist profiles or all-works pages found in saved_pages.';
    $ready = count($artists) > 0;
    foreach ($artists as $a) if (!$a['name'] || count($a['paintings']) !== 5) $ready=false;
    $data=['generated_at'=>date(DATE_ATOM),'ready_for_import'=>$ready,'issues'=>$issues,'artists'=>$artists];
    if (file_put_contents($output.'/results.json', json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)) === false) throw new RuntimeException('Cannot write JSON.');
    $sql = $ready ? t9_sql($artists) : "-- INCOMPLETE INPUT: import blocked. See results.json for missing inputs. No INSERT statements generated.\n";
    if (file_put_contents($output.'/import.sql', $sql) === false) throw new RuntimeException('Cannot write SQL.');
    return $data;
}
function t9_import(mysqli $db, array $data, string $backupDirectory): array
{
    if (empty($data['ready_for_import'])) throw new RuntimeException('Import blocked: each artist needs five valid paintings.');
    $db->set_charset('utf8mb4');
    // Backups stay outside the website and repository; each has a unique name.
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true)) throw new RuntimeException('Cannot create private backup directory.');
    $backup = $backupDirectory.'/before-import-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sql';
    $oldMask = umask(0077);
    $handle = fopen($backup, 'xb');
    umask($oldMask);
    if (!$handle) throw new RuntimeException('Cannot create database backup. Import cancelled.');
    $before=[];
    try {
        $db->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
        $dump="-- Task 9 pre-import backup of Artists, Paintings, Genres and PaintingGenres. Restore into the original database.\nSET NAMES utf8mb4;\n";
        foreach (['Artists','Paintings','Genres','PaintingGenres'] as $table) {
            $create=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_row()[1];
            $dump.="DROP TABLE IF EXISTS `$table`;\n$create;\n";
            $rows=$db->query('SELECT * FROM `'.$table.'`');
            $before[$table]=$rows->num_rows;
            while ($row=$rows->fetch_assoc()) {
                $columns='`'.implode('`,`',array_keys($row)).'`';
                $values=implode(',',array_map('t9_sql_value',array_values($row)));
                $dump.="INSERT INTO `$table` ($columns) VALUES ($values);\n";
            }
        }
        if (fwrite($handle,$dump)!==strlen($dump) || !fflush($handle)) throw new RuntimeException('Incomplete database backup. Import cancelled.');
        fclose($handle); $handle=null;
        $db->commit();
        // Stop on any failed statement and roll back uncommitted changes.
        $db->multi_query(t9_sql($data['artists']));
        do { if ($result=$db->store_result()) $result->free(); } while ($db->more_results() && $db->next_result());
    } catch (Throwable $e) {
        if (is_resource($handle)) fclose($handle);
        $db->rollback();
        throw new RuntimeException('Import failed: '.$e->getMessage().'. Backup: '.$backup, 0, $e);
    }
    $inserted=[];
    foreach ($before as $table=>$count) $inserted[$table]=(int)$db->query('SELECT COUNT(*) FROM `'.$table.'`')->fetch_row()[0]-$count;
    return ['backup'=>$backup,'artists_inserted'=>$inserted['Artists'],'paintings_inserted'=>$inserted['Paintings']];
}
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        if (array_diff(array_slice($argv,1), ['--import'])) throw new RuntimeException('Usage: php scripts/scraping/scraper.php [--import]');
        $data=t9_run(__DIR__.'/saved_pages',__DIR__.'/output',dirname(__DIR__,2).'/images/art/works/square-medium');
        foreach ($data['issues'] as $issue) echo '  - '.$issue."\n";
        foreach ($data['artists'] as $a) { echo $a['requested_name'].': '.count($a['paintings'])."/5 paintings\n"; foreach ($a['issues'] as $issue) echo "  - $issue\n"; }
        echo $data['ready_for_import'] ? "JSON and SQL generated. Review before import.\n" : "JSON generated; SQL import blocked by incomplete input.\n";
        if (in_array('--import',$argv,true)) {
            if (!$data['ready_for_import']) throw new RuntimeException('Import blocked by incomplete input; database unchanged.');
            mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
            $c=require dirname(__DIR__,2).'/config.php';
            $db=new mysqli($c['host'],$c['username'],$c['password'],$c['database'],$c['port']);
            $result=t9_import($db,$data,sys_get_temp_dir().'/artwork-task9-backups');
            $db->close();
            echo 'Backup: '.$result['backup']."\n";
            echo 'Imported '.$result['artists_inserted'].' new artists and '.$result['paintings_inserted']." new paintings. Existing matches were reused; missing artist fields filled when available.\n";
            echo "Refresh http://localhost:8000/art-main.php to view the collection.\n";
        }
        exit($data['ready_for_import'] ? 0 : 1);
    } catch (Throwable $e) { fwrite(STDERR,'Task 9: '.$e->getMessage()."\n"); exit(1); }
}
