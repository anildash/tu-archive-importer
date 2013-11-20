#!/usr/bin/env php
<?php

require 'meekrodb/db.class.php';

//try to open the config file
if (!(include 'config.php')) {
	echo("ERROR: Couldn't open config.php\n");
	echo("In order to run this script you must copy config.php.sample to config.php, then edit the file and supply the appropriate information\n");
	exit(1); //error, couldn't open config.php
}

if ($verbose && $dry_run) {
	echo("*************************\n");
	echo("Script is configed for a dry run. This will preview what will happen, but the database will not be modified.\n");
	echo("To run for real, change \$dry_run to false in config.php\n");
	echo("*************************\n");
}

//set up initial variables
if (!$userid) { $userid = getTwitterUseridNumber($directory); } //if userid isn't in config.php, attempt to detect it
$tweet_files = glob($directory.$directory_js_location);
$tweet_files_count = count($tweet_files);
date_default_timezone_set($timezone);

//informational counts
$tweets_in_files = 0;
$tweets_added = 0;
$tweets_duplicate = 0;
$tweet_files_processed = 0;

if (!checkToProceed($userid, $tweet_files, $timezone, $verbose)) {exit(2);} 

//start the import
$existing = getExistingTweets($table_name, $userid);
if ($verbose) { echo("There are ".count($existing)." existing tweets for userid ".$userid.", located in ".count($tweet_files)." archive files\n"); }
foreach($tweet_files as $file)
{
	$parsedFile = parseFile($file);
	$parsedTweets = parseTweets($parsedFile);
	if ($show_progress) {echo("Processing file ".$tweet_files_processed." of ".$tweet_files_count." - tweets:".count($parsedTweets)."\n");}
	foreach($parsedTweets as $tweet){
		insertTweet($tweet, $table_name, $existing, $dry_run);
	}
	$tweet_files_processed++;
}
//finished the import

if ($verbose) { echo($tweets_added." Tweets were added to thinkup, and ".$tweets_duplicate." tweets were ignored because they already exist\n");}
if ($verbose && $dry_run) { echo("If you liked what you saw, change \$dry_run to false and run this again.\n"); }

exit(0); //completed successfully

//functions below
function checkToProceed($userid, $tweet_files, $timezone, $verbose) {
	if ($userid == '') {
		if ($verbose) {echo("ERROR: Twitter userid number was not supplied, and could not be detected. Please see the readme.\n"); }
		return false;
	}
	if (!$tweet_files) {
		if ($verbose) {echo("ERROR: Unable to find your tweet archive. \n"); }
		return false;
	}
	if (!$timezone || $timezone=='') {
		if ($verbose) {echo("ERROR: Time zone not set. \n"); }
		return false;
	}
	return true;
}

function getTwitterUseridNumber($directory) {
	$userid_location = 'data/js/user_details.js';
	$file_contents = file_get_contents($directory.$userid_location);
	if ($file_contents) {
		$id_string_loc = strpos($file_contents, "\"id\" : \"");
		if ($id_string_loc) {
			$id_string_loc_start = $id_string_loc + 8;
			$id_string = substr($file_contents, $id_string_loc_start);
			$id_end_loc = strpos($id_string, "\",");
			$final_id = substr($id_string, 0, $id_end_loc);
			$twitter_userid_number = trim($final_id);
			if ($twitter_userid_number) {
				return $twitter_userid_number;
			} else {
				return false;
			}
		} else {
			return false;
		}	
	} else {
		return false;
	}
}

function parseFile($file){
	$file_contents = file_get_contents($file);
	$str_data = substr($file_contents, 32);
	$data = json_decode($str_data);
	return $data;
}

function parseTweets($tweets){
	global $tweets_in_files;

	$parsed_tweets = array();

	foreach($tweets as $tweet){

		$parsed_tweet = array(
			'post_id'             	=> $tweet->id_str,
			'author_username'     	=> $tweet->user->screen_name,
			'author_fullname'     	=> $tweet->user->name,
			'author_avatar'       	=> $tweet->user->profile_image_url_https,
			'is_protected'        	=> $tweet->user->protected,
			'author_user_id'      	=> (string)$tweet->user->id,
			'post_text'           	=> (string)$tweet->text,
			'pub_date'            	=> gmdate("Y-m-d H:i:s", strToTime($tweet->created_at)),
			'source'              	=> (string)$tweet->source,
			'network'             	=> 'twitter',
			'author_follower_count' => '0'
		);

		if (isset($tweet->place->full_name)) {
			$parsed_tweet['place'] = (string)$tweet->place->full_name;
		}

		if (isset($tweet->in_reply_to_status_id)) {
			$parsed_tweet['in_reply_to_post_id'] = (string)$tweet->in_reply_to_status_id;
		}

		if (isset($tweet->in_reply_to_user_id)) {
			$parsed_tweet['in_reply_to_user_id'] = (string)$tweet->in_reply_to_user_id;
		}

		array_push($parsed_tweets, $parsed_tweet);
	}
	$tweets_in_files = $tweets_in_files + count($parsed_tweets);
	return $parsed_tweets;

}

function insertTweet($tweet, $table_name, $existing, $dry_run){
	global $tweets_added, $tweets_duplicate;
	if (!isset($existing[$tweet['post_id']])) {
		if (!$dry_run){DB::insert($table_name, $tweet);}
		$tweets_added++;
	} else {
		$tweets_duplicate++;
	}
}

function getExistingTweets($table_name, $userid){
	$existing = array();

	$existingdb = DB::query("SELECT post_id FROM " . $table_name . " WHERE author_user_id=%i AND network='twitter'", $userid);
	foreach ($existingdb as $tweet) {
		$existing[$tweet['post_id']] = true;
	}
	unset($existingdb);

	return $existing;
}
