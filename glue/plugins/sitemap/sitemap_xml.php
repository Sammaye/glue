<?php
class sitemap_xml extends GApplicationComponent{
	function add_to_sitemap($curl, $changefreq = 'hourly', $priority = '0.5'){
		$sitemap = new SimpleXMLElement(ROOT.'/site_map.xml', 0, true);
		$url = $sitemap->addChild('url');
		$url->addChild('loc', $curl);
		$url->addChild('changefreq', $changefreq);
		$url->addChild('priority', $priority);
		$url->addChild('lastmod', date('Y-m-d'));
		$sitemap->saveXML(ROOT.'/site_map.xml');
	}
}