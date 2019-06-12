<?php

namespace Travis;

use Travis\CLI;
use Travis\Date;
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
				// get show information
				$info = static::get_show_info($show);

				// if year is prior to 2008...
				if ($info['year'] < 2008)
				{
					// die
					CLI::fatal('No episodes available prior to 2008.');
				}

				// submit for download
				static::download($info['year'], $info['date'], $info['count'], $info['title'], $show['link'], $show['description']);
			}

			// increment
			$num = $num+10;
		}

		// report
		CLI::write('Done.');
	}

	protected static function get_show_info(array $show)
	{
		// parse title
		$parts = explode(': ', $show['title']);
		if (sizeof($parts) === 2)
		{
			// title type 1
			$type = 1;
			$title = trim($parts[1]);
			$count = trim(str_ireplace(['Car Talk', '#'], ['', ''], $parts[0]));

			// catch oddity
			if (strlen($count) !== 4)
			{
				$type = '1b';
				$title = trim($parts[0]);
				$count = trim(str_ireplace(['Car Talk', '#'], ['', ''], $parts[1]));

				// catch oddity
				if (strlen($count) !== 4)
				{
					$type = '1c';
					$title = $show['date'];
					preg_match('/[0-9]+/', $count, $matches);
					$count = $matches[0];
				}
			}
		}
		else
		{
			// title type 2
			$type = 2;
			$title = $show['date'];
			preg_match('/[0-9]+/', $show['title'], $matches);
			$count = $matches[0];

			// catch oddity
			if (strlen($count) !== 4)
			{
				$type = '2b';
				$parts = explode('#', $show['title']);
				$count = trim($parts[1]);

				// catch oddity
				if (strlen($count) !== 4)
				{
					$type = '2c';
					$parts = explode(' ', $count);
					$count = trim($parts[0]);
				}
			}
		}

		// catch errors...
		if (!$title or !$count or strlen($count) !== 4)
		{
			x($show);
			CLI::write('');
			CLI::error('title = '.$title);
			CLI::error('count = '.$count);
			CLI::write('');
			CLI::fatal('Invalid title (type='.$type.').');
		}

		// figure year from count
		$year = (int) substr($count, 0, 2);
		$year = $year > 50 ? 1900+$year : 2000+$year;

		// make
		$date = Date::make($show['date']);

		// set
		$info = [
			'title' => $title,
			'count' => (int) $count,
			'year' => (int) $year,
			'date' => $date->time() ? $date->format('%Y%m%d') : '?',
		];

		// return
		return $info;
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
				$titles[] = trim($element->plaintext);
			}
		}

		// find the descriptions
		$descriptions = [];
		foreach($html->find('p') as $element)
		{
			if ($element->class === 'teaser')
			{
				$descriptions[] = trim($element->plaintext);
			}
		}

		// find the dates
		$dates = [];
		foreach($html->find('h3') as $element)
		{
			if ($element->class === 'episode-date')
			{
				$dates[] = trim($element->plaintext);
			}
		}

		// find the links
		$links = [];
		foreach($html->find('a') as $element)
		{
			if ($element->class === 'audio-module-listen')
			{
				$links[] = trim($element->href);
			}
		}

		// merge titles and links
		$final = [];
		foreach ($links as $k => $v)
		{
			$final[] = [
				'link' => $links[$k],
				'title' => $titles[$k],
				'date' => $dates[$k],
				'description' => $descriptions[$k],
			];
		}

		// return
		return $final;
	}

	protected static function download($year, $date, $count, $title, $url, $description)
	{
		// set filename
		$filename = 'CarTalk - #'.str_pad($count, 4, 0, STR_PAD_LEFT).' - '.$title.' ('.$date.').mp3';

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

				// save description file
				#file_put_contents(str_ireplace('.mp3', '.txt', $path), $description);

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