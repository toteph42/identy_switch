--
-- 	Identity switch RoundCube Bundle
--
--	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
-- 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
--
-- Created with: https://sqliteonline.com/

ALTER TABLE
	identity_switch
ALTER COLUMN imap_pwd SET DATA TYPE VARCHAR(129);
