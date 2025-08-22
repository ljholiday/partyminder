-- First, remove duplicate invitations keeping only the latest one
DELETE ei1 FROM wp_partyminder_event_invitations ei1
INNER JOIN wp_partyminder_event_invitations ei2 
WHERE ei1.event_id = ei2.event_id 
  AND ei1.invited_email = ei2.invited_email 
  AND ei1.id < ei2.id;

-- Then add unique constraint
ALTER TABLE wp_partyminder_event_invitations 
ADD CONSTRAINT unique_event_email UNIQUE (event_id, invited_email);