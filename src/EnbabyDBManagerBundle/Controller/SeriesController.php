<?php

namespace EnbabyDBManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use EnbabyDBManagerBundle\Entity\Series;
use EnbabyDBManagerBundle\Entity\Books;
use EnbabyDBManagerBundle\Service\LinksDb;

DEFINE("WEBROOT","/var/www/EnbabyDB/web");
DEFINE("SnapshotDB","/AudioLib/snapshots/");
DEFINE("Uploads","/AudioLib/uploads/");


class SeriesController extends Controller
{
	public function indexAction()
	{
		$series = $this->getDoctrine()->getRepository('EnbabyDBManagerBundle:Series')->findAll();
		
		return $this->render('EnbabyDBManagerBundle:Series:index.html.twig', array('series' => $series));
	}

	public function seriesAction($seriesId)
	{
		$em = $this->getDoctrine()->getManager();
		$series = $em->getRepository('EnbabyDBManagerBundle:Series')->findOneById($seriesId);
		$books = array();

		if(!$series) 
		{
			$series = new Series();
			$series->setId($this->getNextSeriesId());
		}else{
			$lc = $this->get('EnbabyDBManagerBundle.Services.LinksDB');
			$books = $lc->getBooksInSeries($seriesId);
		}


		return $this->render('EnbabyDBManagerBundle:Series:series.html.twig',array('series'=>$series,'books'=>$books));
	}

	public function removeAction(Request $request)
	{
		$seriesId = $request->request->get('Id','NULL');
		if($seriesId =='NULL')
		{
			$response = new Response(json_encode(array('MSG' => '-1')));
		}else{
			$em = $this->getDoctrine()->getManager();
			$series = $em->getRepository('EnbabyDBManagerBundle:Series')->find($seriesId);

			if(!$series)
			{
				$response = new Response(json_encode(array('MSG' => '-1')));	
			}else{
				$em->remove($series);
				$em->flush();
				$response = new Response(json_encode(array('MSG' => '1')));
			}
			$response->headers->set('Content-Type', 'application/json');
		}
		return $response;
		
	}
	
	

	public function updateAction(Request $request)
	{


		$seriesId = $request->request->get('Id','NULL');
		if($seriesId == 'NULL')
		{
			$response = new Response(json_encode(array('MSG' => '-1')));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}
		$em = $this->getDoctrine()->getManager();
		$series = $em->getRepository('EnbabyDBManagerBundle:Series')->find($seriesId);
		$new = null;
		if(!$series)
		{
			$series = new Series();
			$new = 1;
		}
		$series->setId($seriesId);
		$series->setDisplayName($request->request->get('DisplayName'));
		$series->setDescription($request->request->get('Description'));
		$series->setLinkToBuy($request->request->get('LinkToBuy'));
		$series->setRank($request->request->get('Rank'));

		if($new) $em->persist($series);
		$em->flush();
		
		$response = new Response(json_encode(array('MSG' => '1')));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
		
	}
	
	public function snapshotuploadAction(Request $request)
	{

		$id = $request->request->get('Id');
		$file = $request->request->get('File');
		$em = $this->getDoctrine()->getManager();
		$series = $em->getRepository('EnbabyDBManagerBundle:Series')->findOneById($id);
		$uploadedFile = WEBROOT . Uploads . $file;
		if($series && file_exists($uploadedFile))
		{
			$dest =SnapshotDB . $id . "_" . $file;
			copy($uploadedFile, WEBROOT . $dest);
			$series->setSnapshot($dest);
			$em->flush();
			$response = new Response(json_encode(array('MSG' => '1', 'Location' => $dest)));
		}else{
			$response = new Response(json_encode(array('MSG' => '-1')));
		}

		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}




	public function getNextSeriesId()
	{
		$nowtime = time();
		$year = ($nowtime / 31556926 + 1970) % 2000 ;
		for($i = 1; $i <100; $i++)
		{
			if($i<10)
			{
				$returnId = 'Z' . $year . '0' . $i;
			}else{
				$returnId = 'Z' . $year . $i;
			}
			$em = $this->getDoctrine()->getManager();
			$series = $em->getRepository('EnbabyDBManagerBundle:Series')->findOneById($returnId);
			if(!$series)
			{
				return $returnId;
			}
			
		}
		
		return 'FULL';
	}
}
