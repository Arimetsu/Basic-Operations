-- Migration: Add E-Signature and Selfie Photo support
-- Date: 2026-03-08
-- Description: The existing application_documents table already supports
-- arbitrary document_type values. This migration documents the new types
-- and ensures the uploads directory structure exists.
--
-- New document_type values used:
--   'e_signature'  - Customer's electronic signature (PNG image from canvas)
--   'selfie_photo' - Customer's real-time selfie photo (JPEG from webcam)
--
-- No schema changes needed - application_documents.document_type is VARCHAR(50)
-- which already accommodates these new values.
--
-- Ensure upload directories exist (run from project root):
--   mkdir -p uploads/id_images/signatures
--   mkdir -p uploads/id_images/selfies

-- Update the document_type column comment for documentation
ALTER TABLE application_documents 
    MODIFY COLUMN document_type VARCHAR(50) NOT NULL 
    COMMENT 'id_front, id_back, e_signature, selfie_photo, proof_of_income, proof_of_address';
