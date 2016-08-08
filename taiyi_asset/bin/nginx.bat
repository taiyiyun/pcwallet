@echo off
set Path=%SystemRoot%\system32;%SystemRoot%;%SystemRoot%\System32\Wbem

"wnmp/php5/php.exe" "wnmp/nginx/www/shell/unlockinit.php"

cd "wnmp"
cd "nginx"
start_nginx.bat