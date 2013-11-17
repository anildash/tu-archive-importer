#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

/* Config */

DB::$user = 'root';
DB::$password = 'root';
DB::$dbName = 'thinkup';

// Change this if you changed your posts table prefix
$table_name = 'tu_posts';

// Twitter user ID (number, can be found in the tweet archive)
$userid = '';

$directory = "tweets/";

$timezone = 'America/New_York';

/* Script Start */

date_default_timezone_set($timezone);

$tweet_files = glob($directory."data/js/tweets/*.js");

foreach($tweet_files as $file)
{
	$parsedFile = parseFile($file);
	$parsedTweets = parseTweets($parsedFile);

	foreach($parsedTweets as $tweet){
		insertTweet($tweet, $table_name);
	}

}

function parseFile($file){

	$file_contents = file_get_contents($file);
	$str_data = substr($file_contents, 32);
	
	$data = json_decode($str_data);

	return $data;

}

function parseTweets($tweets){

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

	return $parsed_tweets;

}

function insertTweet($tweet, $table_name){
	if (!isset($existing[$tweet->id_str])) {
		DB::insert($table_name, $tweet);
	}
}

function getExistingTweets($table_name){
	$existing = array();

	$existingdb = DB::query("SELECT post_id FROM " . $table_name . " WHERE author_user_id=%i AND network='twitter'", $userid);
	foreach ($existingdb as $tweet) {
		$existing[$tweet['post_id']] = true;
	}
	unset($existingdb);

	return $existing;
}