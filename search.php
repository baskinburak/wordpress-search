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
	class Search
	{
		private $db;
		private $query;
		private $table;

		private $search_result;

		public function __construct($query,$table)
		{
			require("db-bridge.php");
			$this->db=new dbBridge();
			$this->query=$query;
			$this->table=$table; // posts or comments
		}
		public function get_result($belowLimit,$aboveLimit)
		{
			$r_set=array_slice($this->search_result,$belowLimit,$aboveLimit-$belowLimit);
			return $this->db->get_info($r_set,$this->table);
		}

		public function do_search()
		{
			$rpn=$this->generate_rpn($this->normalize_tokens($this->tokenize_query()));
		
			$temp_stack=array();
			foreach($rpn as $term)
			{
				if($term=="and" || $term =="or")
				{				
					array_push($temp_stack,$term);
					continue;
				}
				array_push($temp_stack,$this->get_results_for_a_single_word($term));
			}
			$process_stack=array();
			$pop_count=count($temp_stack);
			while($pop_count>0)
			{
				$hold=array_pop($temp_stack);
				array_push($process_stack,$hold);
				$pop_count--;
			}
			$temp_stack=array();
			$pop_count=count($process_stack);
			while($pop_count > 0)
			{
				$hold=array_pop($process_stack);
				switch($hold)
				{
					case "and":
						array_push($temp_stack,$this->set_intersect(array_pop($temp_stack),array_pop($temp_stack)));
						break;
					case "or":
						array_push($temp_stack,$this->set_union(array_pop($temp_stack),array_pop($temp_stack)));
						break;
					default:
						array_push($temp_stack,$hold);
				}
				$pop_count--;
			}
			$this->search_result=array_pop($temp_stack);
		}


		private function set_intersect($arr1,$arr2)
		{
			return array_intersect($arr1, $arr2);
		}

		private function set_union($arr1,$arr2)
		{
			return array_unique(array_merge($arr1, $arr2));
		}
		private function normalize_tokens($tokens)
		{
			$c=0;
			for($i=0;$i<count($tokens);$i++)
			{
				if(strtolower($tokens[$i])=="and" || strtolower($tokens[$i])=="or")
				{
					$c=0;
					continue;
				}
				if($tokens[$i]==")")
				{
					$c=1;
					continue;
				}
				if($i>0 && $tokens[$i-1]=='(')
				{
					$c=1;
					continue;
				}
				$c++;
				if($c==2)
				{
					$inserted=array("AND");
					array_splice($tokens,$i,0,$inserted);
					$i++;
					$c=0;
				}
			}
			return $tokens;
		}
		private function tokenize_query()
		{
			$tokens=array();
			$length_of_query=strlen($this->query);
			for($i=0;$i<$length_of_query;$i++)
			{
				if($this->query[$i]==" ")
					continue;
				if($this->query[$i]=='(' || $this->query[$i]==')')
				{				
					array_push($tokens,$this->query[$i]);
					continue;
				}
				$string="";
				while($i<$length_of_query && $this->query[$i]!=" " && $this->query[$i]!="(" && $this->query[$i]!=")")
				{				
					$string.=$this->query[$i];
					$i++;
				}
				array_push($tokens,$string);
				if($this->query[$i]=='(' || $this->query[$i]==')')
				{				
					array_push($tokens,$this->query[$i]);
					continue;
				}
			}
			return $tokens;
		}



		private function generate_rpn($tokens)
		{
			$output_stack=array();
			$operator_stack=array();
			foreach($tokens as $token)
			{
				$token=strtolower($token);
				switch($token)
				{
					case "or":
					case "and":
					case "(":
						array_push($operator_stack,$token);
						break;
					case ")":
						$hold="";
						while(($hold=array_pop($operator_stack))!="(")
						{
							if($hold==NULL)
							{
								die(json_encode("Badly formatted query!"));
							}
							array_push($output_stack,$hold);
						}
						break;
					default:
						array_push($output_stack,$token);
				}
			}
			while(($hold=array_pop($operator_stack))!=NULL)
			{
				if($hold=="(")
				{
					die(json_encode("Badly formatted query!"));
				}
				array_push($output_stack,$hold);
			}
			return $output_stack;
		}


		private function get_results_for_a_single_word($word)
		{
			return $this->db->get_results($word,$this->table);
		}
		
	}
	


	if(!empty($_GET['query']) && !empty($_GET['table']) && !empty($_GET['limits']))
	{
		$limits=explode("-",$_GET['limits']);
		if($limits[0]!=(string)(int)$limits[0] || $limits[1]!=(string)(int)$limits[1] || $limits[0]>$limits[1])
			die(json_encode("Bad limits!"));
		header("Content-type:application/json");
		$search=new Search($_GET['query'],$_GET['table']);
		$search->do_search();	
		echo json_encode($search->get_result($limits[0],$limits[1]));
		
	}
?>
