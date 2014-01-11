<?php

//
// +---------------------------------------------------------------------------+
// | Facebook Development Platform (beta) PHP5 client                          |   
// +---------------------------------------------------------------------------+
// | Copyright (c) 2006 Facebook, Inc.                                         | 
// | All rights reserved.                                                      |
// |                                                                           |
// | Redistribution and use in source and binary forms, with or without        |
// | modification, are permitted provided that the following conditions        |
// | are met:                                                                  |
// |                                                                           |
// | 1. Redistributions of source code must retain the above copyright         |
// |    notice, this list of conditions and the following disclaimer.          |
// | 2. Redistributions in binary form must reproduce the above copyright      |
// |    notice, this list of conditions and the following disclaimer in the    |
// |    documentation and/or other materials provided with the distribution.   |
// |                                                                           |
// | THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR      |
// | IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES |
// | OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.   |
// | IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT  |
// | NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY     |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT       |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF  |
// | THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.         |
// +---------------------------------------------------------------------------+
// | For help with this library, contact developers-help@facebook.com          |
// +---------------------------------------------------------------------------+
//
include_once 'facebook_conf.php';
include_once 'facebookapi_php5_restlib.php';

$auth_token = $_REQUEST['auth_token'];
if ($_REQUEST['logout']) {
  // clear the cookies
  setcookie('session_key');
  setcookie('uid');
} else {
  $session_key = $_COOKIE['session_key'];
  $uid = $_COOKIE['uid'];
}
if (!$auth_token && !$session_key) {
  header('Location: '.$config['login_url']);
  return;
}

  $client = new FacebookRestClient($config['rest_server_addr'], $config['api_key'], $config['secret'], $session_key, false);
  if (!$session_key) {
    $expires = time() + 3600;
    $session_info = $client->auth_getSession($auth_token);
    $client->session_key = $session_info['session_key'];
    $uid = $session_info['uid'];
    /* You should not blindly trust cookies to provide valid authentication 
     * information since they are transmitted in the clear and can be easily 
     * modified (especially not for the user id).  We encourage you to use your 
     * own more advanced session management system, we merely do this as an 
     * example.  */
    setcookie('session_key', $session_info['session_key'], $expires);
    setcookie('uid', $uid, $expires);
  }
  $my_profile = $client->users_getInfo(array($uid), $profile_field_array);
  $my_name = $my_profile[$uid]['name'];
  $friends_array = array_slice($client->friends_get(), 0, 5);
  $coworkers_array = $client->friends_getTyped('WORKED');
  $albums = $client->photos_getAlbums($uid);
  $album_photos = $client->photos_getFromAlbum($albums[0]['aid'], $uid);
  $friend_profiles = $client->users_getInfo($friends_array, $profile_field_array);
  $events = $client->events_getInWindow(time(), time() + (86400 * 30));
  $photos = $client->photos_getOfUser($uid, 5);
  $messages = $client->messages_getCount();
  $wall_count = $client->wall_getCount();
  $are_friends = $client->friends_areFriends($friends_array[0], $friends_array[1]);


print "<html><head><title>Example PHP5 REST Client</title></head><body>";
print '<a href="sample_client.php?logout=1">logout</a>';
print "<h2>$my_name's info </h2>";

print '<P>Total messages: '.$messages['total'].', unread: '.$messages['unread'];
print '<P>Wall posts: '.$wall_count;
print '<p>Friend one and friend two are '.($are_friends[0] ? '' : 'not ').'friends.';

print_profiles($my_profile);

print "<h2>$my_name's friends</h2>";
print "<HR>";

print_profiles($friend_profiles);

print "<p><h2>Your upcoming events </h2><P>";
if (is_array($events)) {
  foreach ($events as $id => $event) {
    print "<hr>";
    print "<P>Event " . $event['name'];
    print "<P>Event start: " . date('l dS \of F Y h:i:s A' , (int) ($event['start_time']));
    print "<P>Event end: " . date('l dS \of F Y h:i:s A' , (int) ($event['end_time']));
    print "<P>YOUR STATUS IS: " . $event['attending'];
    print "<P>Event owner is IS: " . $event['owner'];
    print "<hr>";
  }
}

if (is_array($coworkers_array)) {
  print "<p><h2>Your coworkers </h2><P>";
  foreach ($coworkers_array as $id) {
    print "<P>Coworker id " . $id;
  }
}
if (is_array($photos)) {
  print "<h2>5 Photos of $my_name</h2>";
  print "<hr>";
  foreach($photos as $photo) {
    $photo_url = htmlspecialchars_decode($photo['src']);
    print "Photo: <img src=\"$photo_url\"><br />\n";
  }
}
if (is_array($album_photos)) {
  print "<h2>Photos from ".$friend_profiles[$friends_array[0]]['name']."'s album: $album[name]</h2>";
  print "<hr>";
  foreach($album_photos as $photo) {
    $photo_src = htmlspecialchars_decode($photo['src']);
    $photo_link = htmlspecialchars_decode($photo['link']);
    print "Photo: <a href='$photo_link'><img src=\"$photo_src\"><br/>$photo[caption]</a><br />\n";
  }
}

print "</body></html>";

function print_profiles($profiles)
{
  if (is_array($profiles)) {
    foreach ($profiles as $id => $profile) {
      foreach($profile as $k => $v)
      {
	print "<P>Profile: $k = ";
	if ($k == 'pic')
	{
	  $pic = htmlspecialchars_decode($profile['pic']);
	  print "<P>Photo: <img src=\"$pic\">\n";

	} else {
	  is_array($v) ? print_r($v) : print($v);
	}
      }
      print "<hr>";
    }
  }
}

?>