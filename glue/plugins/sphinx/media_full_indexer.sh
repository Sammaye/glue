/home/sam/sphinx/bin/indexer --merge media media_delta --rotate --config /home/sam/sphinx/etc/sphinx.conf --merge-dst-range deleted 0 0
#>>/home/sam/sphinx/media_indexlog
#>>/home/sam/sphinx/media_delta_indexlog
/home/sam/sphinx/bin/indexer media_delta --config /home/sam/sphinx/etc/sphinx.conf --rotate