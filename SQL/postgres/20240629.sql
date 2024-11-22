--
-- 	Identity switch RoundCube Bundle
--
--	@copyright	(c) 2024 Forian Daeumling, Germany. All right reserved
-- 	@license 	https://github.com/toteph42/identity_switch/blob/master/LICENSE
--
-- Created with: https://sqliteonline.com/

ALTER TABLE
	identity_switch
ADD COLUMN folders VARCHAR(65534) DEFAULT '';
UPDATE 
	identity_switch
SET folders = JSON_BUILD_OBJECT (
    	'drafts', drafts,
    	'sent', sent,
    	'junk', junk,
    	'trash', trash
    	);    	    	
ALTER TABLE
	identity_switch
DROP drafts;
ALTER TABLE
	identity_switch
DROP sent;
ALTER TABLE
	identity_switch
DROP junk;
ALTER TABLE
	identity_switch
DROP trash;
UPDATE
	identity_switch
-- SAME_AS_IMAP	
SET flags = CASE WHEN CAST(flags & x'0010'::INT AS BOOLEAN)
THEN 
	-- IMAP_SSL
   	CASE WHEN CAST(flags & x'0004'::INT as BOOLEAN)
    THEN 
		-- IMAP_SSL | SMTP_SSL
       	(flags & x'FF0F'::INT) | x'0010'::INT
   	ELSE
		-- flags & IMAP_TLS
      	CASE WHEN cast(flags & x'0008'::INT as BOOLEAN)
        THEN
			-- IMAP_TLS | SMTP_TLS
       		(flags & x'FF0F'::INT) | x'0020'::INT
		ELSE
			-- NONE | SAME_AS_IMAP
		   	flags & x'FF0F'::INT
        END
    END
ELSE 
   	flags
END;
