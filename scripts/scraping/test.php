<?php
// Offline tests. Optional --database uses a disposable test database, never collection rows.
require __DIR__.'/scraper.php';
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message."\n";
}
$root=dirname(__DIR__,2);
$data=json_decode(file_get_contents(__DIR__.'/output/results.json'),true,512,JSON_THROW_ON_ERROR);
check(count($data['artists'])>0 && $data['ready_for_import'], 'saved artists detected and ready for import');
foreach ($data['artists'] as $a) {
    check(count($a['paintings'])===5, $a['requested_name'].' has five paintings');
    foreach ($a['paintings'] as $p) {
        $path=$root.'/images/art/works/square-medium/'.$p['image_stem'].'.'.$p['extension'];
        check($p['title']!=='' && is_file($path) && getimagesize($path)!==false, 'local image and title: '.$p['title']);
    }
}
$temp=sys_get_temp_dir().'/task9-test-'.bin2hex(random_bytes(6));
mkdir($temp); mkdir($temp.'/input'); mkdir($temp.'/images');
try {
    $missing=t9_run($temp.'/input',$temp.'/output',$temp.'/images');
    check(!$missing['ready_for_import'] && !preg_match('/^INSERT /m',file_get_contents($temp.'/output/import.sql')), 'missing files block SQL and replace previous output');
    check(!$missing['artists'] && str_contains(implode(' ', $missing['issues']),'saved_pages'), 'empty input identifies missing saved pages');
    // Synthetic HTML tests parser fields, not purported WikiArt results.
    $image=base64_encode(file_get_contents($root.'/images/art/works/square-medium/'.$data['artists'][0]['paintings'][0]['image_stem'].'.'.$data['artists'][0]['paintings'][0]['extension']));
    $html='<meta property="og:url" content="https://www.wikiart.org/en/le-pho"><h1 itemprop="name">Lê Phổ</h1><time itemprop="birthDate" datetime="1900-01-02">date</time><time itemprop="deathDate" datetime="2000-03-04">date</time><span itemprop="nationality">Việt Nam</span><div itemprop="description">Biography &amp; text</div><ul>';
    for ($i=1;$i<=5;$i++) $html.='<li><img src="data:image/jpeg;base64,'.$image.'"><a class="artwork-name" href="/en/le-pho/test-'.$i.'">Test '.$i.'</a><span class="artwork-year">1938</span></li>';
    file_put_contents($temp.'/input/profile.html',$html.'</ul>');
    $fixture=t9_parse('le-pho','Lê Phổ',$temp.'/input',$temp.'/images');
    check($fixture['name']==='Lê Phổ' && $fixture['birth_date']==='1900-01-02' && $fixture['death_date']==='2000-03-04' && $fixture['nationality']==='Việt Nam' && $fixture['biography']==='Biography & text', 'synthetic profile fields parsed using DOMXPath');
    check(count($fixture['paintings'])===5, 'synthetic embedded images parsed');
    check(t9_image('https://example.com/image.jpg',$temp.'/input/profile.html',$temp.'/input')===null, 'remote images rejected');
    file_put_contents($temp.'/input/local.jpg',base64_decode($image));
    check(t9_image('local.jpg',$temp.'/input/profile.html',$temp.'/input')!==null, 'relative local image accepted');
    check(t9_image('../../outside.jpg',$temp.'/input/profile.html',$temp.'/input')===null, 'outside path rejected');
    $again=t9_parse('le-pho','Lê Phổ',$temp.'/input',$temp.'/images');
    check($again['paintings']===$fixture['paintings'] && count(glob($temp.'/images/*'))===5, 'repeated image extraction reuses identical files');
    $detail='<meta property="og:url" content="https://www.wikiart.org/en/le-pho/all-works"><div class="wiki-detailed-item-container"><a class="masonry-detailed-artwork-title" href="/en/le-pho/test-1">Test 1</a><ul><li><s>Media:</s><span>oil, canvas</span></li><li><s>Dimensions:</s>60 x 45 cm</li><li><s>Style:</s>Test style</li></ul><span itemprop="genre">portrait</span><p itemprop="description">Painting description</p></div>';
    file_put_contents($temp.'/input/details.html',$detail);
    $enriched=t9_parse('le-pho','Lê Phổ',$temp.'/input',$temp.'/images');
    $painting=$enriched['paintings'][0];
    check($painting['genres']===['portrait'] && $painting['medium']==='oil, canvas' && $painting['dimensions']==='60 x 45 cm' && $painting['description']==='Painting description', 'detailed capture merges genres, medium, dimensions and description by source');
    check($enriched['biography']==='Biography & text', 'painting description does not overwrite artist biography');
    $single='<link rel="canonical" href="https://www.wikiart.org/en/le-pho/test-1"><h1 itemprop="name">Painting title, not artist</h1><meta name="description" content="Generic search summary"><p itemprop="description">Specific painting &amp; its story.</p><p itemprop="description">Second paragraph: Việt Nam.</p>';
    file_put_contents($temp.'/input/00-painting.html',$single);
    file_put_contents($temp.'/input/unselected.html','<meta property="og:url" content="https://www.wikiart.org/en/le-pho/test-6"><p itemprop="description">Not a selected painting.</p>');
    $withPaintingPage=t9_parse('le-pho','Lê Phổ',$temp.'/input',$temp.'/images');
    $painting=$withPaintingPage['paintings'][0];
    check($painting['description']==="Specific painting & its story.\n\nSecond paragraph: Việt Nam.", 'individual painting page matches by source URL and preserves paragraph text');
    check($painting['description_page']==='00-painting.html' && $painting['medium']==='oil, canvas', 'painting description supplements detailed-list metadata regardless of filename order');
    check($withPaintingPage['name']==='Lê Phổ' && $withPaintingPage['biography']==='Biography & text', 'painting title and description do not overwrite artist identity or biography');
    check(count($withPaintingPage['paintings'])===5 && $withPaintingPage['paintings'][1]['description']===null, 'unmatched detail pages do not add paintings or change other descriptions');
    file_put_contents($temp.'/input/profile.html', str_replace('<h1 itemprop="name">Lê Phổ</h1>', '<meta itemprop="name" content="Lê Phổ">', $html.'</ul>'));
    $metadataName=t9_parse('le-pho','Lê Phổ',$temp.'/input',$temp.'/images');
    check($metadataName['name']==='Lê Phổ', 'artist name parsed from metadata without a heading');
    $newArtist=str_replace(['le-pho','Lê Phổ'], ['new-test-artist','New Test Artist'], $html.'</ul>');
    $newArtist=str_replace('property="og:url" content=', 'rel="canonical" href=', $newArtist);
    $newArtist=str_replace('<meta rel="canonical"', '<link rel="canonical"', $newArtist);
    file_put_contents($temp.'/input/unrelated-filename.html', $newArtist);
    file_put_contents($temp.'/input/artwork.html', '<meta property="og:url" content="https://www.wikiart.org/en/le-pho/test-1"><h1>Wrong artist name</h1>');
    file_put_contents($temp.'/input/unsupported.html', '<meta property="og:url" content="https://example.com/en/someone"><h1>Unsupported artist</h1>');
    file_put_contents($temp.'/input/orphan.html', '<meta property="og:url" content="https://www.wikiart.org/en/orphan-artist/painting"><p itemprop="description">Needs an artist capture.</p>');
    $discovered=t9_run($temp.'/input',$temp.'/output',$temp.'/images');
    $slugs=array_column($discovered['artists'],'slug');
    check($discovered['ready_for_import'] && count($slugs)===2, 'profile and all-works captures merge into one entry per artist');
    check(in_array('new-test-artist',$slugs,true), 'new artist discovered from canonical URL without a fixed list or matching filename');
    check($discovered['artists'][0]['name']==='Lê Phổ', 'individual artwork page does not overwrite artist name');
    check(count($discovered['issues'])===2 && str_contains(implode(' ', $discovered['issues']),'unsupported.html'), 'unrecognized sources skipped with named diagnostics');
    check(str_contains(implode(' ', $discovered['issues']),'orphan-artist'), 'painting detail without artist captures reports the missing profile');
    file_put_contents($temp.'/input/incomplete.html', '<meta property="og:url" content="https://www.wikiart.org/en/incomplete-artist"><h1>Incomplete Artist</h1>');
    $incompleteArtist=t9_run($temp.'/input',$temp.'/output',$temp.'/images');
    check(!$incompleteArtist['ready_for_import'] && !preg_match('/^INSERT /m',file_get_contents($temp.'/output/import.sql')), 'newly discovered artist without five paintings blocks import');
    foreach (['unrelated-filename.html','artwork.html','unsupported.html','incomplete.html','orphan.html','00-painting.html','unselected.html'] as $file) unlink($temp.'/input/'.$file);
    unlink($temp.'/input/details.html');
    file_put_contents($temp.'/input/profile.html',preg_replace('~data:image/jpeg;base64,[^"]+~','https://example.com/absent.jpg',$html.'</ul>'));
    $invalid=t9_parse('le-pho','Lê Phổ',$temp.'/input',$temp.'/images');
    check(count($invalid['paintings'])===0 && str_contains(implode(' ', $invalid['issues']),'Missing valid local image: Test 1'), 'missing images identify painting and prevent selection');
} finally {
    foreach (glob($temp.'/output/*') ?: [] as $p) unlink($p);
    foreach (glob($temp.'/input/*') ?: [] as $p) unlink($p);
    foreach (glob($temp.'/images/*') ?: [] as $p) unlink($p);
    foreach (['output','input','images'] as $d) if (is_dir($temp.'/'.$d)) rmdir($temp.'/'.$d);
    rmdir($temp);
}
if (in_array('--database',$argv,true)) {
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $c=require $root.'/config.php';
    $db=new mysqli($c['host'],$c['username'],$c['password'],$c['database'],$c['port']); $db->set_charset('utf8mb4');
    // Copy schema definitions into an isolated test database; never copy collection data.
    $definitions=[];
    foreach (['Artists','Paintings','Genres','PaintingGenres'] as $table) $definitions[]=$db->query('SHOW CREATE TABLE '.$table)->fetch_row()[1];
    $testDatabase='task9_test_'.bin2hex(random_bytes(6));
    $db->query('CREATE DATABASE `'.$testDatabase.'` CHARACTER SET utf8mb4');
    try {
    $db->select_db($testDatabase);
    foreach ($definitions as $definition) $db->query($definition);
    $expectedArtists=count($data['artists']);
    $expectedPaintings=array_sum(array_map(fn($a)=>count($a['paintings']),$data['artists']));
    $firstArtist=$data['artists'][0];
    $parts=explode(' ',$firstArtist['name']); $last=array_pop($parts); $first=implode(' ',$parts);
    $seed=$db->prepare("INSERT INTO Artists (FirstName,LastName,Nationality,ArtistLink) VALUES (?,?,'Existing nationality',?)");
    $seed->bind_param('sss',$first,$last,$firstArtist['source']); $seed->execute();
    // Synthetic metadata tests fill-empty behavior; never used for gallery imports.
    $importData=$data;
    $importData['artists'][0]['paintings'][0]['description']='Synthetic painting description';
    $importData['artists'][0]['paintings'][0]['medium']='Synthetic medium';
    $expectedGenres=[]; $expectedLinks=0;
    foreach ($data['artists'] as $artist) foreach ($artist['paintings'] as $painting) {
        foreach (array_unique($painting['genres'] ?? []) as $genre) $expectedGenres[$genre]=true;
        $expectedLinks+=count(array_unique($painting['genres'] ?? []));
    }
    $importData['artists'][0]['nationality']='Synthetic replacement';
    $importData['artists'][0]['biography']='Synthetic test biography';
    $artistQuery=$db->prepare('SELECT Nationality,Details FROM Artists WHERE ArtistLink=?');
    $artistQuery->bind_param('s',$firstArtist['source']);
    $paintingQuery=$db->prepare('SELECT Medium,Description FROM Paintings WHERE WikiLink=?');
    $paintingQuery->bind_param('s',$firstArtist['paintings'][0]['source']);
    $backupDirectory=sys_get_temp_dir().'/task9-import-test-'.bin2hex(random_bytes(6));
    for ($i=0;$i<2;$i++) {
        $import=t9_import($db,$importData,$backupDirectory);
        check(is_file($import['backup']) && (fileperms($import['backup']) & 0777)===0600, 'automatic import creates a private SQL backup');
        $backupText=file_get_contents($import['backup']);
        check(str_contains($backupText,'CREATE TABLE `Artists`') && str_contains($backupText,'CREATE TABLE `Paintings`'), 'backup contains both table definitions');
        check((int)$db->query('SELECT COUNT(*) FROM Genres')->fetch_row()[0]===count($expectedGenres), 'genre records reused across imports');
        check((int)$db->query('SELECT COUNT(*) FROM PaintingGenres')->fetch_row()[0]===$expectedLinks, 'painting genre relationships do not duplicate');
        check((int)$db->query('SELECT COUNT(*) FROM PaintingGenres pg LEFT JOIN Paintings p ON p.PaintingID=pg.PaintingID LEFT JOIN Genres g ON g.GenreID=pg.GenreID WHERE p.PaintingID IS NULL OR g.GenreID IS NULL')->fetch_row()[0]===0, 'genre relationships reference real paintings and genres');
        $paintingQuery->execute(); $storedPainting=$paintingQuery->get_result()->fetch_assoc();
        $artistQuery->execute(); $storedArtist=$artistQuery->get_result()->fetch_assoc();
        check($storedPainting['Medium']==='Synthetic medium', 'painting medium imported');
        check(str_contains($storedPainting['Description'],'Synthetic painting description'), 'painting description imported');
        check($import['paintings_inserted']===($i===0 ? $expectedPaintings : 0), 'automatic import reports newly inserted paintings');
        check($storedArtist['Nationality']==='Existing nationality', 'existing artist metadata preserved');
        check($storedArtist['Details']==='Synthetic test biography', 'missing artist biography filled');
        check((int)$db->query('SELECT COUNT(*) FROM Artists')->fetch_row()[0]===$expectedArtists, 'SQL import '.($i+1).' reuses the existing artist and keeps the detected artist count');
        check((int)$db->query('SELECT COUNT(*) FROM Paintings')->fetch_row()[0]===$expectedPaintings, 'SQL import '.($i+1).' keeps the detected painting count');
        check((int)$db->query('SELECT COUNT(*) FROM Paintings p LEFT JOIN Artists a ON p.ArtistID=a.ArtistID WHERE a.ArtistID IS NULL')->fetch_row()[0]===0, 'SQL relationships valid');
    }
    // Force a failure after the first artist has been inserted: verify transaction rollback.
    $broken=$importData;
    $broken['artists'][0]['name']='Rollback Test';
    $broken['artists'][0]['source']='https://www.wikiart.org/en/rollback-test';
    $broken['artists'][0]['paintings'][0]['image_stem']=str_repeat('x',60);
    $broken['artists'][0]['paintings'][0]['title']='Rollback painting';
    $broken['artists'][0]['paintings'][0]['source']='https://www.wikiart.org/en/rollback-test/painting';
    $db->query("SET SESSION sql_mode='STRICT_ALL_TABLES'");
    $failed=false;
    try { t9_import($db,$broken,$backupDirectory); } catch (RuntimeException $e) { $failed=true; }
    check($failed && (int)$db->query('SELECT COUNT(*) FROM Artists')->fetch_row()[0]===$expectedArtists, 'failed import rolls back artist insertion');
    $incomplete=$importData; $incomplete['ready_for_import']=false;
    $blocked=false;
    try { t9_import($db,$incomplete,$backupDirectory); } catch (RuntimeException $e) { $blocked=true; }
    check($blocked, 'automatic import rejects incomplete extraction');
    } finally {
        $db->query('DROP DATABASE `'.$testDatabase.'`'); $db->close();
        if (isset($backupDirectory) && is_dir($backupDirectory)) {
            foreach (glob($backupDirectory.'/*') ?: [] as $file) unlink($file);
            rmdir($backupDirectory);
        }
    }
}
