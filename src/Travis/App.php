<?php

namespace Travis;

use Travis\CLI;
use Sunra\PhpSimple\HtmlDomParser;

class App
{
	public static function run()
	{
		// init
		$num = 1;

		// while loop
		while ($num < 1000)
		{
			// get links
			$shows = static::get_shows($num);

			// foreach...
			foreach ($shows as $show)
			{
				// parse info
				$string = $show['title'];
				$parts = explode(': ', $string);
				$title = trim($parts[1]);
				$count = trim(str_ireplace('#', '', $parts[0]));
				$url = $show['link'];
				$year = (int) substr($count, 0, 2);
				$year = $year > 50 ? 1900+$year : 2000+$year;

				// submit for download
				static::download($year, $count, $title, $url);
			}

			// increment
			$num = $num+10;
		}

		// report
		CLI::write('Done.');
	}

	protected static function get_shows($num)
	{
		// make url
		$url = 'https://www.npr.org/podcasts/510208/car-talk/partials?start='.$num;

		// pull contents
		$contents = file_get_contents($url);

		// if not found...
		if (!$contents) CLI::fatal('No content found for page #'.$num.'.');

		// parse as html
		$html = HtmlDomParser::str_get_html($contents);

		// find the titles
		$titles = [];
		foreach($html->find('h4') as $element)
		{
			if ($element->class === 'audio-module-title')
			{
				$titles[] = $element->plaintext;
			}
		}

		// find the links
		$links = [];
		foreach($html->find('a') as $element)
		{
			if ($element->class === 'audio-module-listen')
			{
				$links[] = $element->href;
			}
		}

		// merge titles and links
		$final = [];
		foreach ($links as $k => $v)
		{
			$final[] = [
				'title' => $titles[$k],
				'link' => $links[$k],
			];
		}

		// return
		return $final;
	}

	protected static function download($year, $count, $title, $url)
	{
		// set filename
		$filename = 'CarTalk - #'.str_pad($count, 4, 0, STR_PAD_LEFT).' - '.$title.'.mp3';

		// set path
		$path = path('storage/'.$year.'/'.$filename);

		// report
		#CLI::write($filename);

		// if not already saved...
		if (!file_exists($path))
		{
			// make dir
			@mkdir(path('storage/'.$year));

			// download file
			$contents = @file_get_contents($url);

			// if found...
			if ($contents)
			{
				// save file
				file_put_contents($path, $contents);

				// report
				CLI::info($path);
			}
		}

		// if file already exists...
		else
		{
			// report
			#CLI::error($path);
		}
	}
}