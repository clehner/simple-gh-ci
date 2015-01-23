all: cred.php test

cred.php: | cred.example.php
	cp $| $@

test:
	date
	sleep 5
	date
