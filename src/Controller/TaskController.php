<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Text;
use Cake\Core\Configure;
use Cake\Network\Http\Client;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\NotAcceptableException;
use Cake\Network\Exception\NotFoundException;
use Cake\Utility\Xml;
use Cake\ORM\TableRegistry;
use Cake\Cache\Cache;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\I18n\Date;
use Cake\Mailer\Email;
use Pusher;


class TaskController extends AppController
{
	public function initialize()
	{
		parent::initialize();
	}

	public function index(){}

	public function request()
	{
		if ($this->request->is('post') || $this->request->is('get')) {

			$task = $this->request->getData();

			$options = array(
				'cluster' => '',
				'encrypted' =>
			);

			$pusher = new Pusher\Pusher(
				'',
				'',
				'',
				$options
			);

			$data['task'] = $task;
			$pusher->trigger('task-channel', 'new-task', $data);

			$this->set([
				'result' => $task,
				'_serialize' => ['result']
			]);
		}
	}

	public function update()
	{
		if ($this->request->is('post') || $this->request->is('get')) {

			$tasks = $this->request->getData();

			$options = array(
				'cluster' => 'us2',
				'encrypted' => true
			);

			$pusher = new Pusher\Pusher(
				'',
				'',
				'',
				$options
			);

			$data['tasks'] = $tasks;
			$pusher->trigger('task-channel', 'update-tasks', $data);

			$this->set([
				'result' => $tasks,
				'_serialize' => ['result']
			]);

		}
	}

	// Integração com redmine
	const REDMINE_URL = '';
	const REDMINE_KEY = '';

	public function getRedmineTasks() {

		// Get redmine tasks
		$http = new Client();
		$endpoint = 'issues.json';
		$response = [];

		for ($i=1; $i < 5; $i++) {
			$responseHttp = $http->get(Self::REDMINE_URL . $endpoint, [
				'key' => Self::REDMINE_KEY,
				'limit' => 100,
				'page' => $i
			]);
			$responseHttp = json_decode($responseHttp->body, true);
			$response = array_merge_recursive($response, $responseHttp);
		}
		//debug($response);exit;
		$data = [];

		foreach ($response['issues'] as $key => $issue) {

			// Get all to-do tasks
			if ($issue['status']['id'] == 8) {
				$todo = [
					'title' => $issue['subject'],
				];

				if (isset($issue['assigned_to'])) {
					$todo['developer'] = $issue['assigned_to']['name'];
				}

				$tasks['todo'][] = $todo;
			}

			// Get all doing tasks
			if ($issue['status']['id'] == 9) {
				$doing = [
					'title' => $issue['subject'],
				];

				if (isset($issue['assigned_to'])) {
					$doing['developer'] = $issue['assigned_to']['name'];
				}

				$tasks['doing'][] = $doing;
			}

			// Get all done tasks
			if ($issue['status']['id'] == 3) {
				$done = [
					'title' => $issue['subject'],
				];

				if (isset($issue['assigned_to'])) {
					$done['developer'] = $issue['assigned_to']['name'];
				}

				$tasks['done'][] = $done;
			}

		}

		$this->set([
			'tasks' => $tasks,
			'_serialize' => ['tasks']
		]);

	}

}
