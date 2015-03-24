<?php
	if(file_exists("setup"))
	{
		die("You should remove the setup folder.");
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf8"/>
		<title>Search in Wordpress</title>
		<script src="ui/jquery.js"></script>
		<link rel="stylesheet" type="text/css" href="ui/style.css">
		<script>
			$(document).ajaxStart(function(){
				$("#loading").css("display", "block");
			});
			$(document).ajaxComplete(function(){
				$("#loading").css("display", "none");
			});
			$(document).ready(function(){
				var belowLimit="0";
				var aboveLimit="20";
				var query;
				function appendJSON(_json)
				{
					var data=$.parseJSON(_json);
					var len=Object.keys(data).length;
					if(len==1)
					{
						$("#more").css("display","none");
						$("#nomore").css("display","block");
					}
					else
					{
						$("#more").css("display","block");
						if(data[0]=="posts")
						{
							var counter;
							for(counter=1;counter<len;counter++)
							{
								var post_result=$("<div></div>").addClass("post-result");
								var post_title=$("<div></div>").addClass("post-title");
								var title_span=$("<span></span>");
								var title_link=$("<a></a>");
								title_link.attr("href",data[counter]["guid"]);
								title_link.html(data[counter]["post_title"]);
								post_title.html(title_link);
								post_result.append(post_title);
								
								var post_not_title=$("<div></div>").addClass("post-not-title");
								var post_content=$("<div></div>").addClass("post-content");
								post_content.html("<span>"+data[counter]["post_content"]+"..."+"</span>");			
								post_not_title.append(post_content);
								var post_tags=$("<div></div>").addClass("post-tags");
								var post_categories=$("<div></div>").addClass("post-categories");
								post_tags.html("<span class=\"taxonomy-title\">Tags</span>");
								post_categories.html("<span class=\"taxonomy-title\">Categories</span><ul>");
								
								var taxonomyLen=Object.keys(data[counter]["taxonomy"]).length;
								var taxCounter;
								for(taxCounter=0;taxCounter<taxonomyLen;taxCounter++)
								{
									if(data[counter]["taxonomy"][taxCounter]["taxonomy"]=="category")
									{
										var wp_path=window.location.href.substr(0,window.location.href.lastIndexOf("/"));
										wp_path=wp_path.substr(0,wp_path.lastIndexOf("/"));
										wp_path+="/?cat="+data[counter]["taxonomy"][taxCounter]["id"];
										post_categories.append("<li><a href=\""+wp_path+"\">"+data[counter]["taxonomy"][taxCounter]["name"]+"</a></li>");
									}
									else if(data[counter]["taxonomy"][taxCounter]["taxonomy"]=="post_tag")
									{
										var wp_path=window.location.href.substr(0,window.location.href.lastIndexOf("/"));
										wp_path=wp_path.substr(0,wp_path.lastIndexOf("/"));
										wp_path+="/?tag="+data[counter]["taxonomy"][taxCounter]["name"].split(" ").join("-");
										post_tags.append("<li><a href=\""+wp_path+"\">"+data[counter]["taxonomy"][taxCounter]["name"]+"</a></li>");
									}
								}
								post_categories.append("</ul>");
								post_tags.append("</ul>");
								
								post_not_title.append(post_tags);
								post_not_title.append(post_categories);
								post_result.append(post_not_title);
								$("#results").append(post_result);
							}
						}
						else if(data[0]=="comments")
						{
							var counter;
							for(counter=1;counter<len;counter++)
							{
								var post_result=$("<div></div>").addClass("post-result");
								var post_title=$("<div></div>").addClass("post-title");
								var title_span=$("<span></span>");
								var title_link=$("<a></a>");
								title_link.attr("href",data[counter]["guid"]);
								title_link.html("By "+data[counter]["comment_author"]);
								post_title.html(title_link);
								post_result.append(post_title);
								
								var post_not_title=$("<div></div>").addClass("post-not-title");
								var post_content=$("<div></div>").addClass("post-content");
								post_content.html("<span>"+data[counter]["comment_content"]+(data[counter]["comment_content"].length==500 ?"...":"")+"</span>");			
								post_not_title.append(post_content);
								var post_tags=$("<div></div>").addClass("post-tags");
								var post_categories=$("<div></div>").addClass("post-categories");
								post_tags.html("<span class=\"taxonomy-title\">Tags</span>");
								post_categories.html("<span class=\"taxonomy-title\">Categories</span><ul>");
								
								var taxonomyLen=Object.keys(data[counter]["taxonomy"]).length;
								var taxCounter;
								for(taxCounter=0;taxCounter<taxonomyLen;taxCounter++)
								{
									if(data[counter]["taxonomy"][taxCounter]["taxonomy"]=="category")
									{
										var wp_path=window.location.href.substr(0,window.location.href.lastIndexOf("/"));
										wp_path=wp_path.substr(0,wp_path.lastIndexOf("/"));
										wp_path+="/?cat="+data[counter]["taxonomy"][taxCounter]["id"];
										post_categories.append("<li><a href=\""+wp_path+"\">"+data[counter]["taxonomy"][taxCounter]["name"]+"</a></li>");
									}
									else if(data[counter]["taxonomy"][taxCounter]["taxonomy"]=="post_tag")
									{
										var wp_path=window.location.href.substr(0,window.location.href.lastIndexOf("/"));
										wp_path=wp_path.substr(0,wp_path.lastIndexOf("/"));
										wp_path+="/?tag="+data[counter]["taxonomy"][taxCounter]["name"].split(" ").join("-");
										post_tags.append("<li><a href=\""+wp_path+"\">"+data[counter]["taxonomy"][taxCounter]["name"]+"</a></li>");
									}
								}
								post_categories.append("</ul>");
								post_tags.append("</ul>");
								
								post_not_title.append(post_tags);
								post_not_title.append(post_categories);
								post_result.append(post_not_title);
								$("#results").append(post_result);
							}
						}
						else
						{
							$("#results").html(data);
						}
					}
				}
				$("#search-form").submit(function(e){
					$("#results").html("");
					$("#nomore").css("display","none");
					$("#more").css("display","none");			
					belowLimit="0";
					aboveLimit="20";
					query=$("#search-form").serialize();
					$.ajax({
						url:'search.php',
						type:'get',
						dataType:'text',
						contentType: 'application/json; charset=utf-8',
						data:query+"&limits="+belowLimit+"-"+aboveLimit,
						success: function(data)
						{
							appendJSON(data);
						},
						error: function(jqXHR, textStatus, errorThrown)
						{
							$("#results").html(errorThrown);
						}
					});
					e.preventDefault();
					e.unbind();
				});
				$("#more").click(function(e)
				{
					$(this).css("display","none");
					var tmp=belowLimit;
					belowLimit=aboveLimit;
					aboveLimit=(2*parseInt(aboveLimit)-parseInt(tmp)).toString();
					$.ajax({
						url:'search.php',
						type:'get',
						dataType:'text',
						data:query+"&limits="+belowLimit+"-"+aboveLimit,
						success: function(data)
						{
							appendJSON(data);
							$(this).css("display","block");
							//$("#results").append(data);
						},
						error: function(jqXHR, textStatus, errorThrown)
						{
							$("#results").html(errorThrown);
						}
					});
				});
			});
		</script>
	</head>
	<body>
		<div>
			<div id="title">
				<h3>Search in Wordpress</h3>
			</div>
			<form id="search-form">
				<input id="query" type="text" name="query"/><br/>
				<select id="table" name="table">
					<option value="posts">posts</option>
					<option value="comments">comments</option>
				</select>
				<input id="submit" type="submit" value="Search"/>
			</form>
			<div id="results">
				<div id="info">
					<h3>What can you do?</h3>
					<p>You can search for single words,obviously.</p>
					<p>Also,you can create AND-OR networks like "(make OR stop) AND ((searching AND submitting) OR (take AND back))"</p>
					<p>If you place space between terms,they are assumed to be ANDed.</p>
				</div>
			</div>
			<div id="loading">
				<img src="ui/loading.gif" width="50px"/>
			</div>
			<div id="more">
				<img src="ui/more.png"/>				
			</div>
			<div id="nomore">
				<img src="ui/nomore.png"/>
			</div>
		</div>
	</body>
</html>
