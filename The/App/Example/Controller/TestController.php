<?php
	namespace The\App\Example\Controller;

	use The\Core\Util;
	use The\Core\Controller;
	use The\Core\DateTime;
	use The\App\Example\Form\TestForm;
	use The\App\Example\Table\TestTable;

	class TestController extends Controller
	{
		public function fooAction()
		{
			Util::pr('debug message', $_SERVER);

			return 'Hello, world!';
		}

		public function barAction($test)
		{
			$varFromSettings = $this->getApplication()->getConfig()->get('settings.b');

			$form = new TestForm();

			if($form->isSubmitted() && $form->isValid())
			{
				Util::pr( $form->getData() );
			}

			$table = new TestTable();

			$data = array();

			for($i=1; $i<=10; $i++)
			{
				$data[] = array(
					'id' => $i,
					'title' => 'Item #'.$i,
					'date' => new DateTime(),
				);
			}

			$table->load($data);

			return $this->render('@App:Controller:Test:bar', array(
				'test' => $test,
				'setting' => $varFromSettings,
				'form' => $form,
				'table' => $table,
			));
		}

		public function testAction()
		{
			$newsRepository = $this->getApplication()->getRepository('News');

			$allNews = $newsRepository->find();

			foreach($allNews as $news)
			{
				Util::pr($news->getTitle() . ' :: ' . $news->title . ' :: ' . $news['title']);
			}

			$table = new TestTable();

			$table->loadFromQuery( $newsRepository->getTestQuery() );
			
			return $table;
		}
	}