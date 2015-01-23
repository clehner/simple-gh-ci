dirs = builds results

init: config.php $(dirs)

$(dirs):
	mkdir $@
	chmod 777 $@

config.php: | config.example.php
	@echo creating $@
	@sed <$| >$@ \
		's/xxxx*/$(shell </dev/urandom tr -dc _A-Z-a-z-0-9 | head -c24)/'

test: config.php
	php event.php | grep -q 'missing signature'
