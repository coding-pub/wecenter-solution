<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class solution_class extends AWS_MODEL
{
	public function get_solution_info_by_question_id($question_id, $cache = true)
	{
		if (! $question_id)
		{
			return false;
		}

		// 检查问题是否存在

		$questions[$question_id] = $this->fetch_row('question', 'question_id = ' . intval($question_id));
		if(!$questions[$question_id])
		{
			return false;
		}

		if (!$cache)
		{
			$solutions[$question_id] = $this->fetch_row('solution', 'question_id = ' . intval($question_id));
			$solutions[$question_id]['question_id'] = $question_id;
			$solutions[$question_id]['question_content'] = $questions[$question_id]['question_content'];
			$solutions[$question_id]['question_detail'] = $questions[$question_id]['question_detail'];
			$solutions[$question_id]['question_uid'] = $questions[$question_id]['published_uid'];
		}
		else
		{
			static $solutions;

			if ($solutions[$question_id])
			{
				return $solutions[$question_id];
			}

			$solutions[$question_id] = $this->fetch_row('solution', 'question_id = ' . intval($question_id));
			$solutions[$question_id]['question_id'] = $question_id;
			$solutions[$question_id]['question_content'] = $questions[$question_id]['question_content'];
			$solutions[$question_id]['question_detail'] = $questions[$question_id]['question_detail'];
			$solutions[$question_id]['question_uid'] = $questions[$question_id]['published_uid'];
		}

		return $solutions[$question_id];
	}

	/**
	 * 增加问题浏览次数记录
	 * @param int $question_id
	 */
	public function update_views($solution_id)
	{
		if (AWS_APP::cache()->get('update_views_solution_' . md5(session_id()) . '_' . intval($solution_id)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_views_solution_' . md5(session_id()) . '_' . intval($solution_id), time(), 60);

		$this->shutdown_query("UPDATE " . $this->get_table('solution') . " SET view_count = view_count + 1 WHERE solution_id = " . intval($solution_id));

		return true;
	}

	/**
	 *
	 * 保存参考答案内容
	 * @param string $solution_content // 答案内容
	 * @param int $view_cost  // 查看答案消耗积分
	 *
	 * @return boolean true|false
	 */

	public function save_solution($question_id, $solution_content, $view_cost, $uid)
	{
		if (!$ip_address)
		{
			$ip_address = fetch_ip();
		}

		$now = time();

		$to_save_solution = array(
			'solution_content' => htmlspecialchars($solution_content),
			'question_id' => intval($question_id),
			'add_time' => $now,
			'update_time' => $now,
			'has_attach' => 0,
			'published_uid' => intval($uid),
			'view_cost' => intval($view_cost),
			'view_count' => 0,
			'agree_count' => 0,
			'against_count' => 0,
			'ip' => ip2long($ip_address),
			'status' => 0
		);

		$solution_id = $this->insert('solution', $to_save_solution);

		return $solution_id;
	}

	public function update_solution($solution_id, $solution_content, $view_cost)
	{
		if (!$ip_address)
		{
			$ip_address = fetch_ip();
		}

		$now = time();

		$data['solution_content'] = htmlspecialchars($solution_content);
		$data['view_cost'] = intval($view_cost);
		$data['update_time'] = $now;
		$data['agree_count'] = 0;
		$data['against_count'] = 0;
		$data['ip'] = ip2long($ip_address);
		$data['status'] = 0;

		$this->update('solution', $data, 'solution_id = ' . intval($solution_id));
	}

	public function save_user_solution_record($user_id, $solution_id)
	{
		$user_solution_record = array(
			'user_id' => intval($user_id),
			'solution_id' => intval($solution_id),
			'time' => time(),
		);
		return $this->insert('user_solution', $user_solution_record);
	}

	public function get_user_solution_record($user_id, $solution_id)
	{
		$user_solution_record = $this->fetch_row('user_solution', 'user_id = ' . intval($user_id) . ' AND solution_id = ' . intval($solution_id));
		
		return $user_solution_record;
	}

	public function get_solution_list($where = null, $order = 'solution_id DESC', $limit = 10, $page = null)
	{
		$solution_list = $this->fetch_page('solution', $where, $order, $page, $limit);

		return $solution_list;
	}

	public function approve_solution_by_ids($solution_id, $status)
	{
		if (!$solution_id)
		{
			return false;
		}

		if (is_array($solution_id))
		{
			$solution_ids = $solution_id;
		}
		else
		{
			$solution_ids[] = $solution_id;
		}

		array_walk_recursive($solution_ids, 'intval_string');

		if(intval($status) == 0)
		{
			$data['status'] = 1;
		}
		else
		{
			$data['status'] = 0;
		}

		foreach($solution_ids as $solution_id)
		{
			$this->update('solution', $data, 'solution_id = ' . intval($solution_id));	
		}

		return true;
	}

	public function remove_solution_by_ids($solution_id)
	{
		if (!$solution_id)
		{
			return false;
		}

		if (is_array($solution_id))
		{
			$solution_ids = $solution_id;
		}
		else
		{
			$solution_ids[] = $solution_id;
		}

		array_walk_recursive($solution_ids, 'intval_string');

		foreach($solution_ids as $solution_id)
		{
			$this->delete('solution', 'solution_id = ' . intval($solution_id));

			// 删除用户收藏的答案

			$this->delete('user_solution', 'solution_id = ' . intval($solution_id));	
		}

		return true;
	}

	public function update_answer_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}

		return $this->update('question', array(
			'answer_count' => $this->count('answer', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}

	public function update_answer_users_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}

		return $this->update('question', array(
			'answer_users' => $this->count('answer', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}

	public function update_focus_count($question_id)
	{
		if (!$question_id)
		{
			return false;
		}

		return $this->update('question', array(
			'focus_count' => $this->count('question_focus', 'question_id = ' . intval($question_id))
		), 'question_id = ' . intval($question_id));
	}

	public function get_question_thanks($question_id, $uid)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}

		return $this->fetch_row('question_thanks', 'question_id = ' . intval($question_id) . " AND uid = " . intval($uid));
	}

	public function question_thanks($question_id, $uid, $user_name)
	{
		if (!$question_id OR !$uid)
		{
			return false;
		}

		if (!$question_info = $this->get_question_info_by_id($question_id))
		{
			return false;
		}

		if ($question_thanks = $this->get_question_thanks($question_id, $uid))
		{
			//$this->delete('question_thanks', "id = " . $question_thanks['id']);

			return false;
		}
		else
		{
			$this->insert('question_thanks', array(
				'question_id' => $question_id,
				'uid' => $uid,
				'user_name' => $user_name
			));

			$this->shutdown_update('question', array(
				'thanks_count' => $this->count('question_thanks', 'question_id = ' . intval($question_id)),
			), 'question_id = ' . intval($question_id));

			$this->model('integral')->process($uid, 'QUESTION_THANKS', get_setting('integral_system_config_thanks'), '感谢问题 #' . $question_id, $question_id);

			$this->model('integral')->process($question_info['published_uid'], 'THANKS_QUESTION', -get_setting('integral_system_config_thanks'), '问题被感谢 #' . $question_id, $question_id);

			$this->model('account')->update_thanks_count($question_info['published_uid']);

			return true;
		}
	}
}
