dirs = builds results

all: config.php test $(dirs)

$(dirs):
	mkdir $@
	chmod 777 $@

config.php: | config.example.php
	cp $| $@

test:
	date
	sleep 5
	date
