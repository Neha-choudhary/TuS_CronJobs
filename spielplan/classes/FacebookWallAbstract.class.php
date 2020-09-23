<?php
class FacebookWall
{

	public $_use_proxy = false;
	
	private $accessToken = "<accessToken>";
	
	// TuS Germania Lohauserholz
	private $_fb_group_id = "<groupID>";
	
	/**
	 * postToFacebookGroupWall
	 * 
	 * In Facebook and ie Gruppenwand posten
	 * 
	 * @param unknown $fbMessage
	 */
	public function postToFacebookGroupWall($fbMessage)
	{
		try
		{
			$fb = new Facebook\Facebook([ 
					'app_id' => '<appid>',
					'app_secret' => '<appSecret>'
			]);
			$request = $fb->request('POST', '/' . $this->_fb_group_id . '/feed', $fbMessage);
			
			$request->setAccessToken($this->accessToken);
			$response = $fb->getClient()->sendRequest($request);
		} catch (Facebook\Exceptions\FacebookResponseException $e)
		{
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit();
		} catch (Facebook\Exceptions\FacebookSDKException $e)
		{
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit();
		}
	}
	
	/**
	 * deleteOldPosts
	 *
	 * Alte Posts mit dem Tag "Unsere heutige Spiele" automatisch löschen 
	 */
	public function deleteOldPosts()
	{
	try
		{
			$fb = new Facebook\Facebook([ 
					'app_id' => '<appid>',
					'app_secret' => '<appsecret>'
			]);
			$response = $fb->get('/' . $this->_fb_group_id . '/feed', $this->accessToken);
			$aPosts = $response->getDecodedBody();

			if (!empty($aPosts))
			{
				// subtract 3 days from date
				$oldDate = strtotime("-2 days");

				foreach($aPosts['data'] AS $post)
				{
					// Wenn Posts älter als $oldDate ist
					if (strtotime($post['created_time']) < $oldDate)
					{
						// Wenn Posts mit "Unsere heutigen Spiele" beginnt
						if (isset($post['message']) && startsWith($post['message'], "Unsere heutigen Spiele:"))
						{
							$response = $fb->delete('/' . $post['id'], array(), $this->accessToken);
						}
						
						// Wenn Posts mit "Die Ergebnisse unserer heutigen" beginnt
						if (isset($post['message']) && startsWith($post['message'], "Die Ergebnisse unserer heutigen Spiele:"))
						{
							$response = $fb->delete('/' . $post['id'], array(), $this->accessToken);
						}
					}
				}
			}
		} catch (Facebook\Exceptions\FacebookResponseException $e)
		{
			// When Graph returns an error
			echo 'Graph returned an error: ' . $e->getMessage();
			exit();
		} catch (Facebook\Exceptions\FacebookSDKException $e)
		{
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit();
		}
	}
	
}

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}