<?php
/*
	Copyright (C) 2015 Baskın Burak Şenbaşlar
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
	Also add information on how to contact you by electronic and paper mail.
*/
	require_once("../wp-config.php");
	class dbBridge
	{
		private $connection;
		private $postsTable;
		private $commentsTable;
		private $termsTable;
		private $termRelationshipsTable;
		private $termTaxonomyTable;
		public function __construct()
		{
			try
			{
				$this->connection=new PDO("mysql:host=localhost;dbname=".DB_NAME.";charset=utf8",DB_USER,DB_PASSWORD);
				global $table_prefix;				
				$this->postsTable=$table_prefix."posts";
				$this->commentsTable=$table_prefix."comments";
				$this->termsTable=$table_prefix."terms";
				$this->termRelationshipsTable=$table_prefix."term_relationships";
				$this->termTaxonomyTable=$table_prefix."term_taxonomy";
			}
			catch(PDOException $e)
			{
				die(json_encode("There was an error while establishing database connection."));
			}
		}
		public function get_results($word,$selection)
		{
			$prepared=null;
			switch($selection)
			{
				case "posts":
					$prepared=$this->connection->prepare("SELECT ID as id FROM ".$this->postsTable." WHERE MATCH(post_content) AGAINST (:w IN BOOLEAN MODE) AND post_status='publish' AND (post_content REGEXP CONCAT('^[^<>]*',:r2) OR post_content REGEXP CONCAT('^.*<[^<>]*>[^<]*',:r1))");
					break;
				case "comments":
					$prepared=$this->connection->prepare("SELECT comment_ID as id FROM ".$this->postsTable." INNER JOIN (SELECT comment_ID,comment_post_ID FROM ".$this->commentsTable." WHERE MATCH(comment_content) AGAINST (:w IN BOOLEAN MODE) AND (comment_content REGEXP CONCAT('^[^<>]*',:r2) OR comment_content REGEXP CONCAT('^.*<[^<>]*>[^<]*',:r1))) as A ON A.comment_post_ID=ID WHERE post_status='publish'");	
				break;
				default:
					die(json_encode("Bad table selection!"));
			}
			$prepared->bindValue(':w',$word);
			$prepared->bindValue(':r1',$word);
			$prepared->bindValue(':r2',$word);
			$prepared->execute();
			return $prepared->fetchAll(PDO::FETCH_COLUMN,0);
		}
		public function get_info($id_array,$table)
		{
			$result_set=array($table);
			foreach($id_array as $id)
			{
				$prepared=null;
				switch($table)
				{
					case "posts":
						$res=$this->connection->query("SELECT post_title,guid,LEFT(post_content,500) as post_content FROM ".$this->postsTable." WHERE ID=".$id)->fetch(PDO::FETCH_ASSOC);
						$q=$this->connection->query("SELECT term_id as id,name,A.taxonomy FROM ".$this->termsTable." INNER JOIN (SELECT taxonomy,".$this->termRelationshipsTable.".term_taxonomy_id FROM ".$this->termRelationshipsTable." INNER JOIN ".$this->termTaxonomyTable." ON ".$this->termRelationshipsTable.".term_taxonomy_id=".$this->termTaxonomyTable.".term_taxonomy_id WHERE object_id=".$id.") as A ON A.term_taxonomy_id=term_id");
						if($q)
							$res["taxonomy"]=$q->fetchAll(PDO::FETCH_ASSOC);
						array_push($result_set,$res);
						break;
					case "comments":
						$res=$this->connection->query("SELECT comment_ID,comment_author,comment_post_ID,guid,LEFT(comment_content,500) as comment_content FROM ".$this->commentsTable." INNER JOIN ".$this->postsTable." ON comment_post_ID=ID WHERE comment_ID=".$id)->fetch(PDO::FETCH_ASSOC);
						$post_id=$res['comment_post_ID'];
						unset($res['comment_post_ID']);
						$res['guid'].="#comment-".$res['comment_ID'];
						unset($res['comment_ID']);
						$q=$this->connection->query("SELECT term_id as id,name,A.taxonomy FROM ".$this->termsTable." INNER JOIN (SELECT taxonomy,".$this->termRelationshipsTable.".term_taxonomy_id FROM ".$this->termRelationshipsTable." INNER JOIN ".$this->termTaxonomyTable." ON ".$this->termRelationshipsTable.".term_taxonomy_id=".$this->termTaxonomyTable.".term_taxonomy_id WHERE object_id=".$post_id.") as A ON A.term_taxonomy_id=term_id");
						if($q)
							$res["taxonomy"]=$q->fetchAll(PDO::FETCH_ASSOC);
						array_push($result_set,$res);					
						break;
					default:
						die(json_encode("Bad table selection!"));
				}
			}
			return $result_set;
		}
	}
?>
