<?php
		error_reporting(-1);
	ini_set('display_errors', 'On');
	if(isset($_GET['start']) && $_GET['start']=='true')
	{
		include '../../wp-config.php';
		$connection=new PDO("mysql:host=localhost;dbname=".DB_NAME.";charset=utf8",DB_USER,DB_PASSWORD);				
		$postsTable=$table_prefix."posts";
		$commentsTable=$table_prefix."comments";
		$doweneed=$connection->query("SELECT engine FROM information_schema.TABLES where TABLE_SCHEMA='".DB_NAME."' AND TABLE_NAME='".$postsTable."'")->fetch(PDO::FETCH_ASSOC);
		if(strtolower($doweneed['engine'])!="myisam")
		{
			$connection->query("ALTER TABLE ".$postsTable." ENGINE=MyISAM");
		}
		
		$doweneed=$connection->query("SELECT engine FROM information_schema.TABLES where TABLE_SCHEMA='".DB_NAME."' AND TABLE_NAME='".$commentsTable."'")->fetch(PDO::FETCH_ASSOC);
		if(strtolower($doweneed['engine'])!="myisam")
		{
			$connection->query("ALTER TABLE ".$commentsTable." ENGINE=MyISAM");
		}



		$doweneed=$connection->query("SELECT column_name
				FROM INFORMATION_SCHEMA.STATISTICS
				WHERE (index_schema, column_name) = ('".DB_NAME."', 'post_content')
				  AND index_type = 'FULLTEXT'
				ORDER BY seq_in_index;",PDO::FETCH_ASSOC);
		if($doweneed->rowCount()==0)
		{
			$connection->query("ALTER TABLE ".$postsTable." ADD FULLTEXT idx_post_content(post_content)");
		}
		$doweneed=$connection->query("SELECT column_name
				FROM INFORMATION_SCHEMA.STATISTICS
				WHERE (index_schema, column_name) = ('".DB_NAME."', 'comment_content')
				  AND index_type = 'FULLTEXT'
				ORDER BY seq_in_index;",PDO::FETCH_ASSOC);
		if($doweneed->rowCount()==0)
		{
			$connection->query("ALTER TABLE ".$commentsTble." ADD FULLTEXT idx_comment_content(comment_content)");
		}
		
		echo "Done! Remove the setup folder.";
	}
	else
	{
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>Wordpress Search Setup</title>
	</head>
	<body>
		<p>You should read everything to be aware of the thing you are doing.</p>
		<h2>What will this setup script do?</h2>
		<ol>
			<li>It will change the engines of posts and comments tables of wordpress as MyISAM.</li>
			<li>It will add FULLTEXT indexes to post_content column of posts table and comment_content column of comments table.</li>
		</ol>
		<h2>Be cautious!</h2>
		<ol>
			<li>Since this script will change the structure of your tables,it is highly recommended that you take <span style="font-size:25px;font-weight:bold">the backup of your database!</span></li>
			<li>If you have already indexed the columns above there is no need to run this script.You can just close the window and remove setup folder.</li>
		</ol>
		<h2>Limits and effects of the searcher</h2>
		<ol>
			<li>"The minimum and maximum lengths of words to be indexed are defined by the ft_min_word_len and ft_max_word_len system variables." of MySQL.</li>
			<li>"The default minimum value is four characters; the default maximum is version dependent."</li>
			<li>Above two terms suggests that you cannot search for strings which have length less than ft_min_word_len and more than ft_max_word_len.</li>
			<li>If you want to be able to search for smaller strings you should change those system variables <span style="font-size:25px;font-weight:bold">before</span> executing this setup script!</li>
			<li>For me,4 is a good choice.Leaving it as it is is a good idea.</li>
			<li>Because of FULLTEXT indexes,insert time of the database will increase slightly.(~0.4 sec on a personal computer with i5 processor while inserting a post with ~30k words.) </li>
			<li>Because of FULLTEXT indexes,overall size of your database will increase.(about 2x)</li>
		</ol>
		<h2>Important Last Notice</h2>
		<p>Since this script will index all existing posts and comments,it could take long time depending on your database size.For a database which contains ~8k posts each having ~30k words and ~3k comments each having ~200 words on a personal computer with i5 processor,it took 20 minutes to complete everything.So calm down.Start the script.And wait patiently.And when it finishes remove the setup folder.</p>
		<form action="." method="get">
			<input type="hidden" name="start" value="true"/>
			 Pressing this button you can start the setup process! <input type="submit" value="I took the backup of my database and am ready to go!"/>
		</form>
	</body>
</html>
<?php
	}
?>
