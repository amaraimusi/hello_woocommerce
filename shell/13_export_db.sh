#!/bin/sh

echo '作業ディレクトリ'
pwd

echo "ローカルDBのパスワードを入力してください"
read pw

echo 'SQLをエクスポートします。'
#mysqldump -uroot -p$pw --compatible=ansi hello_woocommerce > hello_woocommerce.sql
mysqldump -uroot -p$pw  hello_woocommerce > hello_woocommerce.sql
echo 'エクスポートしました。'

echo 'SQLファイルをサーバーに転送します。'
scp hello_woocommerce.sql amaraimusi@amaraimusi.sakura.ne.jp:www/hello_woocommerce/shell
echo '転送しました。'

echo "------------ 終わり"
cmd /k