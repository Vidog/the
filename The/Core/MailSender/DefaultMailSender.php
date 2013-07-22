<?php
	namespace The\Core\MailSender;

	use The\Core\MailSender;
	use The\Core\Util;

	class DefaultMailSender extends MailSender
	{
		public function send($recipient, $subject, $templateFileName, $params = array(), $senderName = null,
			$senderEmail = null)
		{
			if (!is_array($recipient))
			{
				$recipient = array($recipient);
			}

			require_once dirname(__FILE__) . '/../Lib/SwiftMailer/lib/swift_required.php';
			\Swift::registerAutoload();

			$params = array_merge(
				$params,
				array(
					'server_domain' => 'vsemayki.ru'
				)
			);

			$body = (string)$this->getApplication()->render($templateFileName, $params);

			if (!$senderEmail)
			{
				$senderEmail = $this->getApplication()->getConfig()->get('mail.senders.default.email');
			}

			if (!$senderName)
			{
				$senderName = $this->getApplication()->getConfig()->get('mail.senders.default.name');
			}

			$message = \Swift_Message::newInstance($subject)
			           ->setFrom( array($senderEmail => $senderName) )
			           ->setTo($recipient)
			           ->setBody($body, 'text/html');

			$transport = \Swift_MailTransport::newInstance();


/*			$transport = \Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
			             ->setUsername('har@vsemaiki.ru')
			             ->setPassword('zasada911');*/

			$mailer    = \Swift_Mailer::newInstance($transport);
			return $mailer->send($message);
		}
	}