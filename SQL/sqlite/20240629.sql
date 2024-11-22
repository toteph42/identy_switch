--
-- 	Identity switch RoundCube Bundle
--
--	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
-- 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
--
-- Created with: https://sqliteonline.com/

ALTER TABLE
	`identity_switch`
ADD COLUMN `folders` MEDIUMTEXT DEFAULT '';
UPDATE
	`identity_switch`
SET `folders` = JSON_OBJECT(
	'drafts', `drafts`,
	'sent', `sent`,
	'junk', `junk`,
	'trash', `trash`
);
ALTER TABLE
	`identity_switch`
DROP `drafts`;
ALTER TABLE
	`identity_switch`
DROP `sent`;
ALTER TABLE
	`identity_switch`
DROP `junk`;
ALTER TABLE
	`identity_switch`
DROP `trash`;
UPDATE
	`identity_switch`
-- SAME_AS_IMAP	
SET `flags`= CASE WHEN `flags` & 0x0010  
THEN 
	-- IMAP_SSL
   	CASE WHEN `flags` & 0x0004 
    THEN 
		-- IMAP_SSL | SMTP_SSL
       	(`flags` & 0xFF0F) | 0x0010
   	ELSE
		-- flags & IMAP_TLS
      	CASE WHEN `flags` & 0x0008
        THEN
			-- IMAP_TLS | SMTP_TLS
       		(`flags` & 0xFF0F) | 0x0020
		ELSE
			-- NONE | SAME_AS_IMAP
		   	`flags` & 0xFF0F
        END
    END
ELSE 
   	`flags` 
END;
	