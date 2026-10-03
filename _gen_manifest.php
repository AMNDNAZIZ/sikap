<?php
$constantsContent = file_get_contents(__DIR__ . '/app/Config/Constants.php');
preg_match("/define\('APP_VERSION',\s*'([^']+)'\)/", $constantsContent, $vMatch);
$appVersion = $vMatch[1] ?? '2.0.2';
$root=__DIR__;$dirs=['app','public'];$exclude=['.git','node_modules','public/uploads'];$files=[];
function shouldExclude($rel,$exclude){$rel=str_replace('\\','/',$rel);foreach($exclude as $ex){if($rel===$ex||strpos($rel,$ex.'/')===0)return true;}return false;}
foreach($dirs as $dir){$base=$root.DIRECTORY_SEPARATOR.$dir;if(!is_dir($base))continue;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);foreach($it as $file){if(!$file->isFile())continue;$abs=$file->getPathname();$rel=str_replace('\\','/',ltrim(str_replace($root,'',$abs),'\\/'));if(shouldExclude($rel,$exclude))continue;$content=file_get_contents($abs);$content=str_replace("\r\n","\n",$content);$files[$rel]=md5($content);}}
ksort($files);$manifest=['version'=>$appVersion,'generated'=>date('Y-m-d H:i:s'),'last_update'=>date('Y-m-d H:i'),'total_files'=>count($files),'files'=>$files];
file_put_contents($root.'/update_manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo "Manifest v{$appVersion}: ".count($files)." files\n";
