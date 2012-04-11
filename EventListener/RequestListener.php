<?php

namespace Lunetics\LocaleBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Lunetics\LocaleBundle\LocaleDetection\DetectionPriority;

class RequestListener
{
	/**
	* @var DetectionPriority $detectionPriority The instance of the DetectionPriority class
	*											Contains the possible detection mechanism class names
	*											ordered by priority
	*/
	public $detectionPriority;
	public $defaultLocale;
	public $router;
	public $logger;

	
	public function __construct(DetectionPriority $detectionPriority, 
								$defaultLocale = 'en',
								array $allowedLanguages = array(),
								LoggerInterface $logger = null,
								RouterInterface $router = null)
	{
		$this->detectionPriority = $detectionPriority;
		$this->defaultLocale = $defaultLocale;
		$this->router = $router;
		$this->logger = $logger;
		$this->allowedLanguages = $allowedLanguages;
	}	

	/**
	* This method is called after a kernel.request event. The method receives a GetResponseEvent object
	*
	* @param GetResponseEvent $event A GetResponseEvent object
	*/
	public function onKernelRequest(GetResponseEvent $event)
	{
		$request = $event->getRequest();
		$session = $request->getSession();
		$response = $event->getResponse();

		$detectors = $this->detectionPriority->getDetectorsByPriority();
		foreach($detectors as $key=>$detector)
		{
			if(!empty($detector) && class_exists($detector))
			{
				$engine = new $detector($this->defaultLocale,
										$this->allowedLanguages,
										$request,
										$response,
										$this->router,
										$this->logger
										);

				$engine->processLocaleDetection();
				if($locale = $engine->getDetectedLocale())
				{
					$request->setDefaultLocale($locale);
					$request->setLocale($locale);
					return;
				}
			}
		}
	}
}