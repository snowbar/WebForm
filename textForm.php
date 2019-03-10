<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>textForm</title>
</head>
<body>
  <?php
      $logfile = 'data.txt';
      $run = new WebForm($logfile);
  ?>
  <!--formタグとinputタグを使用して、フォームを作成。-->
  <form action="" method="post">
    お名前　：<input type="text" name="name" value="<?php $run->get_value($logfile,1,$_POST['fix_num'],$_POST['fix_pass']); ?>"></br>
    コメント：<input type="text" name="comments" value="<?php $run->get_value($logfile,2,$_POST['fix_num'],$_POST['fix_pass']); ?>">
    パスワード：<input type="text" name="comments_pass" value="<?php $run->get_value($logfile,4,$_POST['fix_num'],$_POST['fix_pass']); ?>">
    <input type="submit" name = "transmit"value="送信"></br>

    <input type = "hidden" name="hidden" value="<?=$_POST['fix_num']?>">
    <input type = "hidden" name="hidden_pass" value="<?=$_POST['fix_pass']?>">

    削除番号：<input type="text" name="delete_num">
    パスワード：<input type="text" name="delete_pass" value="">
    <input type="submit" name="delete" value="削除"></br>

    修正番号：<input type="text" name="fix_num" value="">
    パスワード：<input type="text" name="fix_pass" value="">
    <input type="submit" name = "fix" value="修正">

  </form>

  <?php $run->main(); ?>

  <?php
      class WebForm{
        //logファイルを指定
        private $logfile;
        private $blank = "<>";

        function __construct($logfile) {
          $this->logfile = $logfile;
        }

        //主な処理を行う場所
        public function main() {

          //POSTによる接続が確認されたらフォームの中身を確認する。
          if ('POST' == $_SERVER['REQUEST_METHOD']) {

            //編集ボタンが押されたときの処理
            if (isset($_POST['fix'])) {
              if ($this->check_num($this->logfile,$_POST['fix_num'])) {
                $this->check_pass($this->logfile,$_POST['fix_num'],$_POST['fix_pass']) ? print "編集中<br>" : print "パスワードが違います。<br>";
              } else {
                print "番号が存在しません。<br>";
              }
            }

            //消去ボタンが押されたとき
            if (isset($_POST['delete'])) {
              if (!$this->check_num($this->logfile,$_POST['delete_num'])) {
                print "番号が存在しません。<br>";
              } elseif (!$this->check_pass($this->logfile,$_POST['delete_num'],$_POST['delete_pass'])) {
                print "パスワードが違います。<br>";
              } else {
                $this->remove_mode($this->logfile,$_POST['delete_num']);
              }
            }

            //送信ボタンが押されたときの処理（編集状態かどうかで動作が変わる。
            if (isset($_POST['transmit'])) {
              if (empty($_POST['comments'])) {
                print "名前がありません。<br>";
              } elseif (empty($_POST['name'])) {
                print "コメントがありません。<br>";
              } else {
                $this->check_pass($this->logfile,$_POST['hidden'],$_POST['hidden_pass']) ?
                $this->fix_mode($this->logfile,$_POST['hidden'],$_POST['name'],$_POST['comments'],$_POST['comments_pass']) :
                $this->normal_mode($this->logfile,$_POST['name'],$_POST['comments'],$_POST['comments_pass']);
              }
            }
            $this->print_comments($this->logfile,$this->blank);
          } else {
            $this->print_comments($this->logfile,$this->blank);
          }
        }

        //第一引数に与えられたファイルを第二引数の値で分解して出力
        public function print_comments($filename,$blank) {
          $contents = file($filename);
          if (count($contents) > 0) {
            foreach ($contents as $value) {
              foreach (explode($blank,$value) as $num => $log) {
                if ($num != 4) {
                  echo $log . ' ';
                }
              }
              echo "<br>";
            }
          }
        }

        //単純にファイルに新しい投稿を書き込む
        public function normal_mode($filename,$name,$comments,$pass) {
          print "更新受け付けました。<br>";
          //入力内容の加工
          $complex = $this->last_num($filename) . $this->blank . htmlentities($name, ENT_QUOTES, 'UTF-8') . $this->blank . htmlentities($comments ,ENT_QUOTES, 'UTF-8') . $this->blank . date('Y/m/d H:i:s') . $this->blank . $pass . "\n";
          //改行文字を加えてファイル書き込む
          file_put_contents($filename,$complex , FILE_APPEND | LOCK_EX);
        }

        //numの番号の行を消す
        public function remove_mode($filename,$num) {
          print "削除が完了しました。<br>";
          $lines = file($filename);
          $fp =fopen($filename,'w');
          fclose($fp);
          foreach ($lines as $index => $line) {
            $arr = explode($this->blank,$line);
            if ($arr[0] != $num) {
              file_put_contents($filename,$line, FILE_APPEND | LOCK_EX);
            }
          }
        }

        //編集を完了させる
        public function fix_mode($filename,$num,$name,$comments,$pass) {
          print "編集完了<br>";
          $lines = file($filename);
          $fp =fopen($filename,'w');
          fclose($fp);
          foreach ($lines as $line) {
            $arr = explode($this->blank,$line);
            if ($arr[0] == $num) {
              $complex = $num . $this->blank . htmlentities($name, ENT_QUOTES, 'UTF-8') . $this->blank . htmlentities($comments ,ENT_QUOTES, 'UTF-8') . $this->blank . date('Y/m/d H:i:s') . $this->blank . $pass  . "\n";
              file_put_contents($filename,$complex, FILE_APPEND | LOCK_EX);
            } else {
              file_put_contents($filename,$line, FILE_APPEND | LOCK_EX);
            }
          }
        }

        //ファイルの最後のコメントの番号を取得
        public function last_num($filename) {
          $contents = file($filename);
          if(count($contents) > 0) {
            $arr = explode($this->blank,$contents[count($contents)-1]);
            return $arr[0] + 1;
          } else {
            return '1';
          }
        }

        //番号が存在するかチェック
        public function check_num($filename,$num) {
            $lines = file($filename);
            foreach ($lines as $line) {
                $line = explode($this->blank,$line);
                if ($line[0] == $num){
                    return true;
                }
            }
            return false;
        }

        //パスワードがあっているかチェック
        public function check_pass($filename,$num,$pass) {
          $lines = file($filename,FILE_IGNORE_NEW_LINES);
          foreach ($lines as $line) {
            $arr = explode($this->blank,$line);
            if ($arr[0] == $num && $arr[4] == $pass) {
                return true;
            }
          }
          return false;
        }

        //名前、コメント、パスワード等をテキストエリアに埋め込む際の関数
        public function get_value($filename,$mode,$num,$pass){
          $line = file($filename,FILE_IGNORE_NEW_LINES);
          $ans;
          if(count($line) > 0) {
             foreach ($line as $value) {
                $arr = explode($this->blank,$value);
                if ($arr[0] == $num && $arr[4] == $pass) {
                   $ans = $arr[$mode];
                }
            }
          }
          if(empty($ans)) {
            switch ($mode) {
              case 1:
                echo '例）名前';
                break;
              case 2:
                echo "例）コメント";
                break;
              case 4:
                echo "例）password";
                break;
              default:
                break;
            }
          } else {
            echo $ans;
          }
        }
      }
  ?>
</body>
</html>
