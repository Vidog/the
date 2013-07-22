<?php
	namespace The\Core\Implant;

	use The\Core\Util;
	use The\Core\TemplateEngine\TemplateRawInput;

	trait HTMLElementImplant
	{
		protected $tagName;
		protected $withoutCloseTag = false;
		protected $attributes = array();
		protected $content;

		public function setContent($content)
		{
			$this->content = $content;

			return $content;
		}

		public function setTagName($tagName)
		{
			$this->tagName = $tagName;

			return $this;
		}

		public function getTagName()
		{
			return $this->tagName;
		}

		public function setWithoutCloseTag($withoutCloseTag)
		{
			$this->withoutCloseTag = $withoutCloseTag;

			return $this;
		}

		public function getWithoutCloseTag()
		{
			return $this->withoutCloseTag;
		}

		public function setId($value)
		{
			return $this->setAttribute('id', $value);
		}

		public function getId()
		{
			return $this->getAttribute('id');
		}

		public function setDataAttribute($name, $value)
		{
			return $this->setAttribute('data-'.$name, $value);
		}

		public function getDataAttribute($name)
		{
			return $this->getAttribute('data-'.$name);
		}

		public function getDataAttributes()
		{

		}

		public function setAttribute($name, $value)
		{
			$this->attributes[$name] = $value;

			return $this;
		}

		public function removeAttribute($name)
		{
			unset($this->attributes[$name]);

			return $this;
		}

		public function getAttribute($name)
		{
			return Util::arrayGet($this->attributes, $name);
		}

		public function getAttributes()
		{
			return $this->attributes;
		}

		public function addClass($name)
		{
			$classes = $this->getClasses();
			$classes[$name] = $name;
			return $this->setAttribute('class', implode(' ', $classes) );
		}

		public function removeClass($name)
		{
			$classes = $this->getClasses();
			unset($classes[$name]);
			return $this->setAttribute('class', implode(' ', $classes) );
		}

		public function getClasses()
		{
			$classes = explode( ' ', Util::ifNull($this->getAttribute('class'), '') );

			$res = array();
			foreach($classes as $class)
			{
				$res[$class] = $class;
			}

			return $res;
		}

		protected function buildStyles($styles)
		{
			$res = '';

			foreach($styles as $key => $value)
			{
				$res .= $key.': '.$value.'; ';
			}

			return $res;
		}

		public function addStyle($name, $value)
		{
			$styles = $this->getStyles();

			$styles[$name] = $value;

			$this->setAttribute('style', $this->buildStyles($styles));

			return $this;
		}

		public function removeStyle($name)
		{
			$styles = $this->getStyles();

			unset($styles[$name]);

			$this->setAttribute('style', $this->buildStyles($styles));
			
			return $this;
		}

		public function getStyles()
		{
			$styles = explode( '; ', Util::ifNull($this->getAttribute('style'), '') );

			$res = array();
			foreach($styles as $style)
			{
				if(!$style)
				{
					continue;
				}
				list($key, $value) = explode(':', $style);
				$res[trim($key)] = trim($value);
			}

			return $res;
		}

		public function buildProperties()
		{
			$res = array();

			foreach($this->getAttributes() as $attribute => $value)
			{
				$res[] = $attribute.'="'.$value.'"';
			}

			return implode(' ', $res);
		}

		public function openTag()
		{
			$tagName = $this->getTagName();
			$tagProperties = $this->buildProperties();

			if($tagProperties)
			{
				$tagProperties = ' '.$tagProperties;
			}

			return new TemplateRawInput('<'.$tagName.''.$tagProperties.''.($this->getWithoutCloseTag() ? ' /' : '').'>');
		}

		public function closeTag()
		{
			$tagName = $this->getTagName();

			return $this->getWithoutCloseTag() ? '' : new TemplateRawInput('</'.$tagName.'>');
		}

		public function getContent()
		{
			return $this->content;
		}
	}