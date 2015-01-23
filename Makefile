dirs = builds results

all: config.php $(dirs) test

$(dirs):
	mkdir $@
	chmod 777 $@

config.php: | config.example.php
	@echo creating $@
	@sed <$| >$@ \
		's/xxxx*/$(shell </dev/urandom tr -dc _A-Z-a-z-0-9 | head -c24)/'

test:
	date
