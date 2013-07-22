<?php
	namespace The\App\Example\Model;

	use The\Core\Util;
	use The\Core\Model;

	class NewsModel extends Model
	{
		const TABLE_NAME = 'news';

		/**
		 * @param $id
		 * @return NewsModel
		 */
		public function setId($id)
		{
			return parent::setId($id);
		}

		public function getId()
		{
			return parent::getId();
		}

		/**
		 * @param $title
		 * @return NewsModel
		 */
		public function setTitle($title)
		{
			return parent::setTitle($title);
		}

		public function getTitle()
		{
			return parent::getTitle();
		}

		/**
		 * @param $text
		 * @return NewsModel
		 */
		public function setText($text)
		{
			return parent::setText($text);
		}

		public function getText()
		{
			return parent::getText();
		}

		/**
		 * @param $createdAt
		 * @return NewsModel
		 */
		public function setCreatedAt($createdAt)
		{
			return parent::setCreatedAt($createdAt);
		}

		public function getCreatedAt()
		{
			return parent::getCreatedAt();
		}


	}