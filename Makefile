dirs = builds results
randstr = $(shell </dev/urandom 2>/dev/null tr -dc _A-Z-a-z-0-9 | head -c24)

init: config.php $(dirs)

$(dirs):
	mkdir $@
	chmod 777 $@

config.php: | config.example.php
	@echo creating $@
	@sed <$| >$@ \
		's/xxxx*/$(call randstr)/'

test: config.php
	php event.php | grep -q 'missing signature'
