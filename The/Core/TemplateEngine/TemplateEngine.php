<?php
	namespace The\Core\TemplateEngine;

	use The\Core\Yaml;
	use The\Core\Util;
	use The\Core\Implant\ApplicationImplant;

	class TemplateEngine
	{
		use ApplicationImplant;

		private $templatesDir;
		private $cacheDir;
		private $baseConstructions = array();
		private $lineConstructions = array();
		private $blockConstructions = array();
		private $cacheEnabled = false;
		private $data = array();

		public function compiler($func)
		{
			$args = func_get_args();
			unset($args[0]);
			return call_user_func_array(array($this->compiler, $func), $args);
		}

		public function addData($dataType, $data)
		{
			if(!isset($this->data[$dataType]))
			{
				$this->data[$dataType] = array();
			}
			$this->data[$dataType][] = $data;

			Util::registry('TemplateEngineStorage', $this->data);

			return $this;
		}

		public function getData($dataType = null)
		{
			return $dataType === null ? $this->data : Util::arrayGet($this->data, $dataType);
		}

		public function __construct($templatesDir, $storage)
		{
			/**
			@TODO kostyle
			*/
			$this->setApplication( Util::getApplication() );

			$this->templatesDir = $templatesDir;
			$this->storage = $storage;
			$this->compiler = new TemplateCompiler();

			$th = $this;

			$this->addBaseConstruction('start', function($th, $type, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level)
			{
				return $th->buildTemplate($blocks, $variables, $tplEx, $level + 1);
			});

			$this->addBaseConstruction('raw', function($th, $type, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level) use ($th)
			{
				return $cBlock['buf'];
			});

			$this->addBaseConstruction('echo', function($th, $type, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level) use ($th)
			{
				preg_match('/\{\{(.*)\}\}/si', $cBlock['buf'], $m);
				$expr = isset($m[1]) ? $m[1] : '';
				if(isset($tplEx['_fromFilter']) && $tplEx['_fromFilter'])
				{
					return $th->parseExpression($expr, $variables, $tplEx, $level);
				}else
				{
					return $th->compiler('makeEcho', $th->parseExpression($expr, $variables, $tplEx, $level));
				}
			});

			$this->addBaseConstruction('raw', function($th, $type, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level) use ($th)
			{
				return $cBlock['buf'];
			});

			$this->addBaseConstruction('construction', function($th, $type, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level) use ($th)
			{
				if($type == 'line')
				{
					return $th->callLineConstruction($cBlock, $variables, $tplEx, $level + 1);
				}else
				{
					return $th->callBlockConstruction($cBlock, $blocks, $cEnd, $variables, $tplEx, $level + 1);
				}
			});

			$this->addLineConstruction('set', function($th, $block, $variables, $tplEx, $level)
			{
				if( preg_match('/\{\%[\s+]?set[\s+]?(.*)[\s+]?\%\}/si', $block['buf'], $m) )
				{
					$expr = trim($m[1]);
					$tok = $th->getExpressionTokens($expr);
					$leftPart = array();
					$rightPart = array();
					$hasSign = false;
					foreach($tok as $v)
					{
						if($hasSign)
						{
							$rightPart[] = $v;
						}else
						{
							if($v['type'] == 'sign' && $v['data'][1] == '=')
							{
								$hasSign = true;
								continue;
							}
							$leftPart[] = $v;
						}
					}

					if(sizeof($leftPart) > 1)
					{
						throw new \Exception('Wrong set');
					}

					$leftPart = reset($leftPart);
					$leftPart = $leftPart['data'];

					$rightPart = implode(' ', array_map(function($v){  return $v['type'] == 'expr' ? implode('', array_map(function($vv){ return $vv[1]; }, $v['data'])) : $v['data'][1]; }, $rightPart));
					$rightPart = $th->parseExpression($rightPart, $variables, $tplEx, $level + 1);

					if(sizeof($leftPart) > 1)
					{
						list($obj, $parts) = $th->getObjectParts($leftPart);

						$fn = function($x) use ($th, $variables, $tplEx, $level)
						{
							if(preg_match('/\[(.*?)\]/i', $x, $m))
							{
								return $th->parseExpression($m[1], $variables, $tplEx, $level + 1);
							}else
							{
								return $th->parseExpression($x, $variables, $tplEx, $level + 1);
							}
						};
						$fx = function($x) use ($th, $variables, $tplEx, $level)
						{
							if(preg_match('/\[(.*?)\]/i', $x, $m))
							{
								return $th->parseExpression($m[1], $variables, $tplEx, $level + 1);
							}else
							{
								return $th->parseExpression('\''.$x.'\'', $variables, $tplEx, $level + 1);
							}
						};

						$tpl = $th->makeVariableExpr(array($obj), $variables, $tplEx, $level + 1);
						$lastP = $parts[sizeof($parts) - 1];
						unset($parts[sizeof($parts) - 1]);
						foreach($parts as $p)
						{
							$tpl = $th->compiler('getObjectProperty', $tpl, $fx($p[0][1]));
						}
						$tpl = $th->compiler('setObjectProperty', $tpl, $fx($lastP[0][1]), $rightPart);

						return $tpl;
						#return $th->compiler('setVariable', $leftPart[0][1], $rightPart);
						#return $th->compiler('setVariable', 'a', $rightPart);
					}else
					{
						return $th->compiler('setVariable', $leftPart[0][1], $rightPart);
					}					
				}else
				{
					throw new \Exception('Wrong set');
				}
			});

			$this->addLineConstruction('include', function($th, $block, $variables, $tplEx, $level)
			{
				preg_match('/\{\%\s*include\s+([\w:\@]+)(\s+with\s+(.*)|)\s*?\%\}/si', $block['buf'], $m);

				/**
				@TODO kostyle
				*/

				$json = isset($m[3]) ? $th->makeObjectExpr(array( array('br2', $m[3]) ), $variables, $tplEx, $level + 1) : false;
				if(!trim($json))
				{
					$json = 'array()';
				}
				#$tplExtends = str_replace(':', '/', $m[1]).'.html.twig';
				$tplExtends = $m[1];

				return $th->compiler('makeEcho', 'new \The\Core\TemplateEngine\TemplateRawInput( \The\Core\Util::getApplication()->renderPartial(\''.$tplExtends.'\', '.$json.') )');
			});

			$this->addLineConstruction('render', function($th, $block, $variables, $tplEx, $level)
			{
				preg_match('/\{\%\s*render\s+([\w:]+)(\s+with\s+(.*)|)\s*?\%\}/si', $block['buf'], $m);

				list($controllerName, $actionName) = explode(':', $m[1].':');

				$json = isset($m[3]) ? $th->makeObjectExpr(array( array('br2', $m[3]) ), $variables, $tplEx, $level + 1) : false;
				if(!trim($json))
				{
					$json = 'array()';
				}

				return $th->compiler('makeEcho', 'new \The\Core\TemplateEngine\TemplateRawInput( \The\Core\Util::getApplication()->callControllerAction(\''.$controllerName.'\', \''.$actionName.'\', '.$json.') )');
			});

			$this->addBlockConstruction('csslink', function($th, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level)
			{
				$uri = trim($blocks[1]['block']['buf']);

				$th->addData('css', $uri);

				return $th->compiler('makeCSSLink', $uri); 
			});

			$this->addBlockConstruction('jslink', function($th, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level)
			{
				$uri = trim($blocks[1]['block']['buf']);

				$th->addData('js', $uri);

				return $th->compiler('makeJSLink', $uri); 
			});

			$this->addBlockConstruction('if', function($th, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level)
			{
				preg_match('/\{\%[\s+]?(if|elseif)[\s+]?\((.*)\)[\s+]?\%\}/si', $cBlock['buf'], $m);
				$bl = isset($m[1]) ? $m[1] : '';
				$expr = isset($m[2]) ? $m[2] : '';

				$boolExpr = $th->parseBoolExpression($expr, $variables, $tplEx, $level + 1);

				return $th->compiler('makeIf', $bl, $boolExpr, $th->buildTemplate($blocks, $variables, $tplEx, $level + 1));
			});

			$this->addBlockConstruction('for', function($th, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level)
			{
				preg_match('/\{\%[\s+]?(for)[\s+]?(.*)[\s+]?\%\}/si', $cBlock['buf'], $m);
				$bl = isset($m[1]) ? $m[1] : '';
				$expr = isset($m[2]) ? $m[2] : '';
				
				$parts = explode(' in ', $expr, 2);
				if(sizeof($parts) >= 2)
				{
					$kk = explode(',', $parts[0], 2);
					if(sizeof($kk) >= 2)
					{
						$kName = trim($kk[0]);
						$vName = trim($kk[1]);
					}else
					{
						$kName = '';
						$vName = trim($kk[0]);
					}
					$ex = $th->parseExpression($parts[1], $variables, $tplEx, $level + 1);
					$ind = mt_rand(100000, 999999);
					$kVar = '$k'.$ind;
					$vVar = '$v'.$ind;
					$exp = $ex.' as '.($kVar ? ($kVar.' => '.$vVar) : $vVar);
				}else
				{
					throw new \Exception('Wrong for');
				}

				return $th->compiler('makeForeach', $ex, $exp, $kName, $kVar, $vName, $vVar, $th->buildTemplate($blocks, $variables, $tplEx, $level + 1));
			});

			$this->addLineConstruction('elseif', function($th, $block, $variables, $tplEx, $level)
			{
				preg_match('/\{\%[\s+]?(if|elseif)[\s+]?\((.*)\)[\s+]?\%\}/si', $block['buf'], $m);
				$bl = isset($m[1]) ? $m[1] : '';
				$expr = isset($m[2]) ? $m[2] : '';
				
				return $th->compiler('makeElseif', $th->parseBoolExpression($expr, $variables, $tplEx, $level + 1));
			});

			$this->addLineConstruction('else', function($th, $block, $variables, $tplEx, $level)
			{
				return $th->compiler('makeElse');
			});

			$this->addLineConstruction('endif', function($th, $block, $variables, $tplEx, $level)
			{
				return $th->compiler('makeEndif');
			});

			$this->addBlockConstruction('block', function($th, $cBlock, $blocks, $cEnd, $variables, $tplEx, $level)
			{
				return $th->buildTemplate($blocks, $variables, $tplEx, $level + 1);
			});
		}

		public function getExpressionTokens($expr)
		{
			$ex = '<?'.$expr.'?>';
			$t = token_get_all($ex);
			unset($t[0], $t[sizeof($t)]);

			$tokens = array();
			foreach($t as $v)
			{
				$tp = $v[0];
				if(!is_numeric($tp))
				{
					$tp = '';
					$tpName = '';
					$vx = $v[0];
				}else
				{
					$tpName = is_numeric($tp) ? token_name($tp) : $tp;
					$vx = $v[1];
				}
				$tokens[] = array('type' => $tpName, 'data' => $vx);
			}

			$tokens2 = array();
			$inString = false;
			$inBlock = false;
			$br1 = 0;
			$br2 = 0;
			$br3 = 0;
			$buf = '';
			foreach($tokens as $t)
			{
				if($br1 > 0)
				{
					$buf .= $t['data'];
					if($t['data'] == '(')
					{
						$br1++;
					}
					if($t['data'] == ')')
					{
						$br1--;
					}
					if($br1 <= 0)
					{
						$tokens2[] = array('br1', $buf);
						$buf = '';
					}
					continue;
				}
				if($br2 > 0)
				{
					$buf .= $t['data'];
					if($t['data'] == '{')
					{
						$br2++;
					}
					if($t['data'] == '}')
					{
						$br2--;
					}
					if($br2 <= 0)
					{
						$tokens2[] = array('br2', $buf);
						$buf = '';
					}
					continue;
				}
				if($br3 > 0)
				{
					$buf .= $t['data'];
					if($t['data'] == '[')
					{
						$br3++;
					}
					if($t['data'] == ']')
					{
						$br3--;
					}
					if($br3 <= 0)
					{
						$tokens2[] = array('br3', $buf);
						$buf = '';
					}
					continue;
				}
				if($t['data'] == '(')
				{
					$br1 = 1;
					if(trim($buf))
					{
						$tokens2[] = array('raw', $buf);
					}
					$buf = $t['data'];
					continue;
				}
				if($t['data'] == '{')
				{
					$br2 = 1;
					if(trim($buf))
					{
						$tokens2[] = array('raw', $buf);
					}
					$buf = $t['data'];
					continue;
				}
				if($t['data'] == '[')
				{
					$br3 = 1;
					if(trim($buf))
					{
						$tokens2[] = array('raw', $buf);
					}
					$buf = $t['data'];
					continue;
				}
				if($t['type'] == 'T_STRING')
				{
					if(trim($t['data']) == 'not')
					{
						$tokens2[] = array('sign', '!');
						$buf = '';
						continue;
					}
					if(trim($buf))
					{
						$tokens2[] = array('raw', $buf);
					}
					$tokens2[] = array('string', $t['data']);
					$buf = '';
					continue;
				}
				if($t['type'] == 'T_CONSTANT_ENCAPSED_STRING')
				{
					if(trim($buf))
					{
						$tokens2[] = array('raw', $buf);
					}
					$tokens2[] = array('escaped', $t['data']);
					continue;
				}
				if(in_array($t['type'], array(
					'T_BOOLEAN_AND',
					'T_BOOLEAN_OR',
					'T_IS_GREATER_OR_EQUAL',
					'T_IS_SMALLER_OR_EQUAL',
					'T_LOGICAL_AND',
					'T_LOGICAL_OR',
					'T_SR',
					'T_SL',
					'T_IS_EQUAL',
					'T_IS_NOT_EQUAL'
				)))
				{
					$tokens2[] = array('sign', $t['data']);
					continue;
				}
				if(!$t['type'])
				{
					if(trim($buf))
					{
						$tokens2[] = array('raw', $buf);
					}
					if(in_array($t['data'], array('.', '|')))
					{
						$tokens2[] = array($t['data'], $t['data']);
						continue;
					}elseif(in_array($t['data'], array('[', ']')))
					{
						$tokens2[] = array('br3', $t['data']);
						continue;
					}else
					{
						$tokens2[] = array('sign', $t['data']);
						continue;
					}
				}
				if(in_array($t['type'], array('T_LNUMBER', 'T_DNUMBER')))
				{
					$tokens2[] = array('number', $t['data']);
					continue;
				}
				if($t['type'] == 'T_WHITESPACE')
				{
					continue;
				}

				$tokens2[] = array('?', $t['data']);
				continue;
			}

			$tokens3 = array();
			$buf = array();
			foreach($tokens2 as $v)
			{
				if($v[0] == 'sign')
				{
					$tokens3[] = array('type' => 'expr', 'data' => $buf);
					$tokens3[] = array('type' => 'sign', 'data' => $v);
					$buf = array();
					continue;
				}
				$buf[] = $v;
			}
			$tokens3[] = array('type' => 'expr', 'data' => $buf);

			return $tokens3;
		}

		public function getObjectParts($data)
		{
			$obj = $data[0];
			unset($data[0]);

			$parts = array();
			$buf = array();

			foreach($data as $v)
			{
				if($v[0] == '.')
				{
					if(sizeof($buf) > 0 && reset($buf))
					{
						$parts[] = $buf;
					}
					$buf = array();
					continue;
				}
				if($v[0] == 'br3')
				{
					if(sizeof($buf) > 0 && reset($buf))
					{
						$parts[] = $buf;
					}
					$parts[] = array($v);
					$buf = array();
					continue;
				}
				$buf[] = $v;
			}
			if(sizeof($buf) > 0 && reset($buf))
			{
				$parts[] = $buf;
				$buf = array();
			}

			return array($obj, $parts);
		}

		public function makeObjectMethodExpr($data, $variables, $tplEx, $level)
		{
			list($obj, $parts) = $this->getObjectParts($data);

			$th = $this;

			$fn = function($x) use ($th, $variables, $tplEx, $level)
			{
				if(preg_match('/\[(.*?)\]/i', $x, $m))
				{
					return $th->parseExpression($m[1], $variables, $tplEx, $level + 1);
				}else
				{
					return $th->parseExpression($x, $variables, $tplEx, $level + 1);
				}
			};
			$fx = function($x) use ($th, $variables, $tplEx, $level)
			{
				if(preg_match('/\[(.*?)\]/i', $x, $m))
				{
					return $th->parseExpression($m[1], $variables, $tplEx, $level + 1);
				}else
				{
					return $th->parseExpression('\''.$x.'\'', $variables, $tplEx, $level + 1);
				}
			};

			$isM = function($p)
			{
				foreach($p as $v)
				{
					if($v[0] == 'br1')
					{
						return true;
					}
				}
				return false;
			};

			$tpl = $this->makeVariableExpr(array($obj), $variables, $tplEx, $level + 1);
			foreach($parts as $p)
			{
				if($isM($p))
				{
					$tpl = $this->compiler('callObjectFunction', $tpl, $p[0][1], $fn($p[1][1]));
				}else
				{
					$tpl = $this->compiler('getObjectProperty', $tpl, $fx($p[0][1]));
				}
			}

			return $tpl;
		}

		public function makeObjectPropertyExpr($data, $variables, $tplEx, $level)
		{
			return $this->makeObjectMethodExpr($data, $variables, $tplEx, $level);
		}

		public function makeFunctionExpr($data, $variables, $tplEx, $level)
		{
			preg_match('/^\((.*)\)$/si', trim($data[1][1]), $m);
			if(isset($m[1]))
			{
				$expr = $this->parseExpression($m[1], $variables, $tplEx, $level + 1);
				if(isset($tplEx['_isFilter']) && $tplEx['_isFilter'])
				{
					if(is_array($tplEx['_filterExpression']))
					{
						$ex = implode('', array_map(function($x){ return $x[1]; }, $tplEx['_filterExpression']));
						preg_match('/^\((.*)\)$/si', trim($ex), $m);
						unset($tplEx['_isFilter'], $tplEx['_filterExpression']);
						$ex = $this->parseExpression($m[1], $variables, $tplEx, $level + 1);
						#var_dump($ex);
					}else
					{
						$ex = $tplEx['_filterExpression'];
					}
					return $this->compiler('callFilter', $data[0][1], ($expr ? $ex.', '.$expr : $ex));
				}else
				{
					return $this->compiler('callFunction', $data[0][1], $expr);
				}
			}else
			{
				throw new \Exception('Wrong function');
			}
		}

		public function makeVariableExpr($data, $variables, $tplEx, $level)
		{
			if(in_array($data[0][1], array('true', 'false')))
			{
				return $data[0][1];
			}
			return $this->compiler('getVariable', $data[0][1]);
		}

		public function makeExprExpr($data, $variables, $tplEx, $level)
		{
			preg_match('/^\((.*)\)$/si', trim($data[0][1]), $m);
			if(isset($m[1]))
			{
				return '('.$this->parseExpression($m[1], $variables, $tplEx, $level + 1).')';
			}else
			{
				throw new \Exception('Wrong expression');
			}
		}

		public function makeObjectExpr($data, $variables, $tplEx, $level)
		{
			$dt = trim($data[0][1]);
			$dt = substr($dt, 1, strlen($dt) - 2);

			$ex = '<?'.$dt.'?>';
			$t = token_get_all($ex);
			unset($t[0], $t[sizeof($t)]);

			$tokens = array();
			foreach($t as $v)
			{
				$tp = $v[0];
				if(!is_numeric($tp))
				{
					$tp = '';
					$tpName = '';
					$vx = $v[0];
				}else
				{
					$tpName = is_numeric($tp) ? token_name($tp) : $tp;
					$vx = $v[1];
				}
				$tokens[] = array('type' => $tpName, 'data' => $vx);
			}

			$res = array();

			$inValue = false;
			$bufKey = array();
			$bufValue = array();
			$br2 = 0;
			foreach($tokens as $tok)
			{
				$type = $tok['type'];
				$data = $tok['data'];

				if($inValue)
				{
					if($br2 > 0)
					{
						$bufValue[] = $data;
						if($data == '{')
						{
							$br2++;
						}
						if($data == '}')
						{
							$br2--;
						}
						continue;
					}
					if($data == '{')
					{
						$bufValue[] = $data;
						$br2 = 1;
						continue;
					}
					if($data == ',')
					{
						$res[implode('', $bufKey)] = implode('', $bufValue);
						$inValue = false;
						$bufKey = array();
						$bufValue = array();
						continue;
					}
					$bufValue[] = $data;
				}else
				{
					if($br2 > 0)
					{
						$bufKey[] = $data;
						if($data == '{')
						{
							$br2++;
						}
						if($data == '}')
						{
							$br2--;
						}
						continue;
					}
					if($data == '{')
					{
						$bufKey[] = $data;
						$br2 = 1;
						continue;
					}
					if($data == ':')
					{
						$inValue = true;
						continue;
					}
					if($data == ',')
					{
						$res[] = implode('', $bufKey);
						$bufKey = array();
						$bufValue = array();
						$inValue = false;
						continue;
					}
					$bufKey[] = $data;
				}
			}
			$key = implode('', $bufKey);
			$value = implode('', $bufValue);
			if($value)
			{
				$res[$key] = $value;
			}else
			{
				$res[] = $key;
			}

			$data = $res;

			$res = 'array(';
			foreach($data as $key => $value)
			{
				$key = $this->parseExpression($key, $variables, $tplEx, $level + 1);
				$value = $this->parseExpression($value, $variables, $tplEx, $level + 1);

				$res .= $key.' => '.$value.',';
			}
			$res .= ')';

			return $res;
		}

		public function makeStringExpr($data, $variables, $tplEx, $level)
		{
			return $this->compiler('getString', $data[0][1]);
		}

		public function makeUnknownExpr($data, $variables, $tplEx, $level)
		{
			#return isset($data[0]) ? (is_numeric($data[0][1]) ? $data[0][1] : '$this->getNone1()') : '$this->getNone2()';
			return isset($data[0]) ? (is_numeric($data[0][1]) ? $data[0][1] : '') : '';
		}

		public function makeNoneExpr($data, $variables, $tplEx, $level)
		{
			return 'NONE';
		}

		public function parseBoolExpression($expr, $variables, $tplEx, $level)
		{
			return $this->parseExpression($expr, $variables, $tplEx, $level);
		}

		public function parseExpression($expr, $variables, $tplEx, $level)
		{
			if(is_array($expr))
			{
				$tokens = $expr;
			}else
			{
				$tokens = $this->getExpressionTokens($expr);
			}

			$tpx = array();
			$signMap = array(
				'~' => '.',
			);
			foreach($tokens as $v)
			{
				if($v['type'] == 'sign')
				{
					$s = $v['data'][1];
					$tpx[] = isset($signMap[$s]) ? $signMap[$s] : $s;
				}else
				{
					$hasBr1 = false;
					$hasBr2 = false;
					$hasBr3 = false;
					$hasPoint = false;
					$hasString = false;
					$hasEscaped = false;
					$hasFilter = false;
					$tp = '';
					foreach($v['data'] as $dt)
					{
						if($dt[0] == '|')
						{
							$hasFilter = true;
						}
						if($dt[0] == '.')
						{
							$hasPoint = true;
						}
						if($dt[0] == 'string')
						{
							$hasString = true;
						}
						if($dt[0] == 'br1')
						{
							$hasBr1 = true;
						}
						if($dt[0] == 'br2')
						{
							$hasBr2 = true;
						}
						if($dt[0] == 'br3')
						{
							$hasBr3 = true;
						}
						if($dt[0] == 'escaped')
						{
							$hasEscaped = true;
						}
						$tp .= $dt[0].' ';
					}
					$tx = 'None';
					if($hasFilter)
					{
						$tok = array();
						$filt = array();
						$buf = array();
						$isFilt = false;
						foreach($v['data'] as $dt)
						{
							if($isFilt)
							{
								if($dt[0] == '|')
								{
									$filt[] = $buf;
									$buf = array();
									continue;
								}
								$buf[] = $dt;
							}else
							{
								if($dt[0] == '|')
								{
									$isFilt = true;
									continue;
								}
								$tok[] = $dt;
							}
						}
						$filt[] = $buf;

						$exp = $this->parseExpression(implode('', array_map(function($x){ return $x[1]; }, $tok)), $variables, $tplEx, $level + 1);
						foreach($filt as $f)
						{
							$ex = implode('', array_map(function($x){ return $x[1]; }, $f));
							if(sizeof($f) < 2)
							{
								$ex .= '()';
							}
							$tplEx['_isFilter'] = true;
							$tplEx['_filterExpression'] = $exp;
							$exp = $this->parseExpression($ex, $variables, $tplEx, $level + 1);
						}

						return $exp;
					}
					if($hasString)
					{
						if($hasPoint || $hasBr3)
						{
							if($hasBr1)
							{
								$tx = 'ObjectMethod';
							}else
							{
								$tx = 'ObjectProperty';
							}
						}else
						{
							if($hasBr1)
							{
								$tx = 'Function';
							}else
							{
								$tx = 'Variable';
							}
						}
					}else
					{
						if($hasBr1)
						{
							$tx = 'Expr';
						}else
						{
							if($hasBr2)
							{
								$tx = 'Object';
							}else
							{
								if($hasEscaped)
								{
									$tx = 'String';
								}else
								{
									$tx = 'Unknown';
								}
							}
						}
					}
					$fn = 'make'.$tx.'Expr';
					$tpx[] = $this->$fn($v['data'], $variables, $tplEx, $level + 1);
				}
			}

			return implode(' ', $tpx);
		}

		public function getExpressionParts($expression, $splitBy = array())
		{

		}

		public function tplStringToPHP($expression)
		{
			$parts = $this->getExpressionParts($expression);
		}

		public function tplBooleanToPHP($expression)
		{
			
		}

		private function addBaseConstruction($constructionName, $callback)
		{
			$this->baseConstructions[$constructionName] = $callback;
		}

		private function removeBaseConstruction($constructionName)
		{
			unset($this->baseConstructions[$constructionName]);
		}

		public function addLineConstruction($constructionName, $callback)
		{
			$this->lineConstructions[$constructionName] = $callback;
		}

		public function removeLineConstruction($constructionName)
		{
			unset($this->lineConstructions[$constructionName]);
		}

		public function addBlockConstruction($constructionName, $callback)
		{
			$this->blockConstructions[$constructionName] = $callback;
		}

		public function removeBlockConstruction($constructionName)
		{
			unset($this->blockConstructions[$constructionName]);
		}

		public function callBaseConstruction($type, $cBlock, $blocks, $cEnd, array $variables = array(), array $tplEx = array(), $level)
		{
			$c = isset($this->baseConstructions[$cBlock['block']['type']]) ? $this->baseConstructions[$cBlock['block']['type']] : '';
			if(!$c)
			{
				return false;
			}else
			{
				$th = $this;
				return call_user_func_array($c, array($th, $type, $cBlock['block'], $blocks, isset($cEnd['block']) ? $cEnd['block'] : $cEnd, $variables, $tplEx, $level));
			}
		}

		public function callLineConstruction($construction, array $variables = array(), array $tplEx = array(), $level)
		{
			$c = isset($this->lineConstructions[$construction['block']]) ? $this->lineConstructions[$construction['block']] : '';
			if(!$c)
			{
				return false;
			}else
			{
				$th = $this;
				return call_user_func_array($c, array($th, $construction, $variables, $tplEx, $level));
			}
		}

		public function callBlockConstruction($construction, $blocks, $cEnd, array $variables = array(), array $tplEx = array(), $level)
		{
			$c = isset($this->blockConstructions[$construction['block']]) ? $this->blockConstructions[$construction['block']] : '';
			if(!$c)
			{
				return false;
			}else
			{
				$th = $this;
				return call_user_func_array($c, array($th, $construction, $blocks, $cEnd, $variables, $tplEx, $level));
			}
		}

		private function getConstructions($tpl)
		{
			$len = strlen($tpl);
			$skip = 0;
			$buf = '';
			$blockType = '';
			$inBlock = false;
			$inStr = false;
			$inMStr = false;
			$inComment = false;
			$blocks = array();
			$line = 0;
			$ix = 0;
			$blocks[] = array('type' => 'start', 'pos' => array(0, 0), 'buf' => '');
			for($i=0; $i<$len; $i++)
			{
				if($skip > 0)
				{
					$skip--;
					continue;
				}
				$cp = isset($tpl[$i-1]) ? $tpl[$i-1] : '';
				$c = $tpl[$i];
				$c2 = isset($tpl[$i+1]) ? $tpl[$i+1] : '';
				$cc = $c.$c2;
				if($inBlock)
				{
					if($inComment)
					{
						if($cc == '#}')
						{
							$inComment = false;
							#$inBlock = false;
							#$buf .= $cc;
							#$blocks[] = array('type' => 'comment', 'pos' => array($ix, $i + 1), 'buf' => $buf);
							#$buf = '';
							$skip = 1;
							continue;
						}else
						{
						}
					}else
					{
						if($inStr)
						{
							if($c == '\'' && $cp != '\\')
							{
								$inStr = false;
							}
							$buf .= $c;
							continue;
						}
						if($c == '\'' && !$inMStr)
						{
							$inStr = true;
							$buf .= $c;
							continue;
						}
						if($inMStr)
						{
							if($c == '"' && $cp != '\\')
							{
								$inMStr = false;
							}
							$buf .= $c;
							continue;
						}
						if($c == '"')
						{
							$inMStr = true;
							$buf .= $c;
							continue;
						}
						if($cc == '{#')
						{
							#$buf .= $cc;
							$inComment = true;
							$skip = 1;
							$ix = $i;
							continue;
						}
						if($blockType == '{%' && $cc == '%}')
						{
							$buf .= $cc;
							preg_match('/\{\%\s+?(\w+).*\%\}/si', $buf, $m);
							$type = isset($m[1]) ? strToLower($m[1]) : '';
							$blocks[] = array('type' => 'construction', 'block' => $type, 'pos' => array($ix, $i), 'buf' => $buf);
							$buf = '';
							$blockType = $cc;
							$inBlock = false;
							$skip = 1;
							$ix = $i + 2;
							continue;
						}
						if($blockType == '{{' && $cc == '}}')
						{
							$buf .= $cc;
							$blocks[] = array('type' => 'echo', 'pos' => array($ix, $i), 'buf' => $buf);
							$buf = '';
							$blockType = $cc;
							$inBlock = false;
							$skip = 1;
							$ix = $i + 2;
							continue;
						}
						$buf .= $c;
					}
				}else
				{
					if(in_array($cc, array('{#', '{%', '{{')))
					{
						if($cc == '{#')
						{
							$inComment = true;
						}
						$blocks[] = array('type' => 'raw', 'pos' => array($ix, $i), 'buf' => $buf);
						$buf = $inComment ? '' : $cc;
						$blockType = $cc;
						$inBlock = true;
						$skip = 1;
						$ix = $i;
						continue;
					}
					$buf .= $c;
				}

			}
			$blocks[] = array('type' => 'raw', 'pos' => array($ix, $i), 'buf' => $buf);
			$blocks[] = array('type' => 'endstart', 'pos' => array($i, $i), 'buf' => '');

			return $blocks;
		}

		private function getBigBlocks()
		{
			$blocks = array();
			foreach(array_keys($this->blockConstructions) as $v)
			{
				$blocks[$v] = '';
			}
			return array_merge(array('start' => ''), $blocks);
			#array('start' => '', 'block' => '', 'for' => '', 'if' => '')
		}

		private function treeConstructions(array $constr)
		{
			$res = array();

			$bigBlocks = $this->getBigBlocks();
			$bigBlocksEnd = array(

			);

			$fx = function($fx, $arr, $ind = 0, $blockName = 'start', $level = 1) use ($bigBlocks)
			{
				$res = array();
				if(is_array($blockName))
				{
					$tpx = $blockName[0];
					$blx = $blockName[1];
				}else
				{
					$tpx = $blockName;
					$blx = '';
				}
				$skip = 0;
				for($i=$ind; $i<sizeof($arr); $i++)
				{
					if($skip > 0)
					{
						$skip--;
						continue;
					}
					$c = $arr[$i];
					$tp = $c['type'];
					$bl = isset($c['block']) ? $c['block'] : '';
					if(isset($bigBlocks[$tp]) || ($tp == 'construction' && isset($bigBlocks[$bl])))
					{
						$r = $fx($fx, $arr, $i+1, array('construction', 'end'.$bl), $level + 1);
						array_unshift($r[0], array('type' => 'line', 'block' => $c));
						$res[] = array('type' => 'block', 'block_type' => array($tp, $bl), 'block' => $r[0]);
						$skip = $r[1] - $i;
						continue;
					}else
					{
						$res[] = array('type' => 'line', 'block' => $c);
						if($tp == $tpx && $bl == $blx)
						{
							break;
						}
					}
				}
				return array($res, $i);
			};

			$r = $fx($fx, $constr);
			$res = $r[0];

			return $res;
		}

		public function buildTemplate(array $constr, array $variables = array(), array $tplEx = array(), $level = 1)
		{
			$tpl = '';

			foreach($constr as $c)
			{
				if($c['type'] == 'line')
				{
					$tpl .= $this->callBaseConstruction('line', $c, array(), array(), $variables, $tplEx, $level + 1);
				}else
				{
					$cBlock = reset($c['block']);
					$cEnd = $c['block'][sizeof($c['block'])-1];
					unset($c['block'][0]);
					unset($c['block'][sizeof($c['block'])]);
					$blocks = $c['block'];
					$tplEx['_parent'] = isset($c['_parent']) ? $c['_parent'] : (isset($tplEx['_parent']) ? $tplEx['_parent'] : false);
					$tpl .= $this->callBaseConstruction('block', $cBlock, $blocks, $cEnd, $variables, $tplEx, $level + 1);
				}
			}
			return $tpl;
		}

		public function parseEchoVariable($code, array $variables = array(), array $tplEx = array(), $level = 1)
		{
			return $code;
		}

		public function getTemplateData($templateName, array $variables = array(), array $tplEx = array())
		{
			// $removeDoubleSlashes = function($s)
			// {
			// 	$s = str_replace('\\', '/', $s);
			// 	while(substr_count($s, '//'))
			// 	{
			// 		$s = str_replace('//', '/', $s);
			// 	}
			// 	return $s;
			// };

			// $tplDir = $this->templatesDir;

			// $fname = $removeDoubleSlashes( $tplDir.$templateName );

			$fname = $templateName;

			$tpl = file_get_contents($fname);

			/*
			$tpl = preg_replace('/\<\?.*\?\>/si', '', $tpl);
			$tpl = preg_replace('/\<\?.* /si', '', $tpl);
			*/
			$tpl = preg_replace('/\<\?/', '&lt;?', $tpl);
			$tpl = preg_replace('/\?\>/', '?&gt;', $tpl);
			$tpl = preg_replace('/\{\#.*\#\}/si', '', $tpl);

			$constr = $this->getConstructions($tpl);
			$constr = $this->treeConstructions($constr);

			$c = reset($constr);
			$cBlock = reset($c['block']);
			$cEnd = $c['block'][sizeof($c['block'])-1];
			unset($c['block'][0]);
			unset($c['block'][sizeof($c['block'])]);
			$blocks = $c['block'];
			$tplBlocks = array();
			$tplExtends = false;
			foreach($blocks as $b)
			{
				if($b['type'] == 'block' && $b['block_type'][0] == 'construction' && $b['block_type'][1] == 'block')
				{
					preg_match('/\{\%\s+?block\s+?(\w+)\s+?\%\}/si', $b['block'][0]['block']['buf'], $m);
					if(isset($m[1]) && $m[1])
					{
						$tplBlocks[$m[1]] = $b;
						continue;
					}else
					{
						throw new \Exception('Wrong block');
					}
				}
				
				if(!isset($b['block']['type']))
				{
					continue;
				}

				if($b['block']['type'] == 'construction' && $b['block']['block'] == 'extends')
				{
					preg_match('/\{\%\s+?extends\s+?(.*)\s+?\%\}/si', $b['block']['buf'], $m);
					if(isset($m[1]) && $m[1])
					{
						$fileName = $this->getTemplateFileName($m[1], 'extends');
						$tplExtends = $this->parseEchoVariable($fileName, $variables);
					}else
					{
						throw new \Exception('Wrong extends');
					}
				}
			}

			if($tplExtends)
			{
				$constr = $this->getTemplateData($tplExtends, $variables, array());
				foreach($constr[0]['block'] as $k => $v)
				{
					if($v['type'] == 'block' && $v['block_type'][0] == 'construction' && $v['block_type'][1] == 'block')
					{
						preg_match('/\{\%\s+?block\s+?(\w+)\s+?\%\}/si', $v['block'][0]['block']['buf'], $m);
						if(isset($m[1]) && $m[1])
						{
							if(isset($tplBlocks[$m[1]]))
							{
								$tplBlocks[$m[1]]['_parent'] = $v;
								$constr[0]['block'][$k] = $tplBlocks[$m[1]];
							}
						}else
						{
							throw new \Exception('Wrong block');
						}
					}
				}
			}

			return $constr;
		}

		public function getTemplateFileName($templateFileName, $type = null)
		{
			return $this->getApplication()->getTemplateFileName($templateFileName, $type);
		}

		public function render($templateName, array $variables = array(), $templateEnv = null, $templateAdapter = null, $compiledTemplateAdapter = null)
		{
			if($templateEnv === null)
			{
				$templateEnv = '\The\Core\TemplateEngine\TemplateEnv';
			}

			if($templateAdapter === null)
			{
				$templateAdapter = '\The\Core\TemplateEngine\TemplateAdapter';
			}

			if($compiledTemplateAdapter === null)
			{
				$compiledTemplateAdapter = '\The\Core\TemplateEngine\TemplateCompiledAdapter';
			}

			$originalTemplateName = $templateName;

			$hash = md5($templateName);

			$dir = '/dev/shm/';
			$isDebug = $this->getApplication()->getIsDebug();
			$compiledFile = $dir.'template_'.$hash.'.compiled.php';
			$reader = $this->getApplication()->getFileReader();

			$isDebug = true;

			if(!($env = Util::registry('TemplateEnv:'.$templateEnv)))
			{
				$env = new $templateEnv();
				Util::registry('TemplateEnv:'.$templateEnv, $env);
			}

			$tpl = '';
			$adapter = '';

			$res = new Template();

			if (!$isDebug && $this->getApplication()->getStorage()->exists('compiled_template', $compiledFile))
			{
				$tpl = $this->getApplication()->getStorage()->get('compiled_template', $compiledFile);
				$adapter = $compiledTemplateAdapter;
			}else
			{
				$constr = $this->getTemplateData($templateName, $variables);
				$tpl = $this->buildTemplate($constr, $variables, array());
				$adapter = $templateAdapter;

				if(!$isDebug)
				{
					$this->getApplication()->getStorage()->set('compiled_template', $compiledFile, $tpl);
				}				
			}

			$res->init($env, $tpl, $adapter, $variables);

			return $res;
		}
	}