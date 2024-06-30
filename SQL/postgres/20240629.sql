-- Created with: https://sqliteonline.com/

ALTER TABLE
	identy_switch
ADD COLUMN folders VARCHAR(65534) DEFAULT '';
UPDATE 
	identy_switch
SET folders = JSON_BUILD_OBJECT (
    	'drafts', drafts,
    	'sent', sent,
    	'junk', junk,
    	'trash', trash
    	);    	    	
ALTER TABLE
	identy_switch
DROP drafts;
ALTER TABLE
	identy_switch
DROP sent;
ALTER TABLE
	identy_switch
DROP junk;
ALTER TABLE
	identy_switch
DROP trash;
UPDATE
	identy_switch
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
