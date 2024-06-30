--  Created with phpmyadmin

ALTER TABLE
	`identy_switch`
ADD COLUMN `folders` MEDIUMTEXT DEFAULT '';
UPDATE 
	`identy_switch`
SET `folders` = JSON_OBJECT(
    	'drafts', `drafts`,
    	'sent', `sent`,
    	'junk', `junk`,
    	'trash', `trash`
    	);    	    	
ALTER TABLE
	`identy_switch`
DROP `drafts`,
DROP `sent`,
DROP `junk`,
DROP `trash`;
UPDATE
	`identy_switch`
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
	