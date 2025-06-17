#!/usr/bin/env python3
"""
OCR Text Extraction Script
AI-Powered Fake News Detection System
"""

import sys
import pytesseract
from PIL import Image
import argparse
import os

def extract_text_from_image(image_path):
    """
    Extract text from image using OCR
    """
    try:
        # Check if image file exists
        if not os.path.exists(image_path):
            raise FileNotFoundError(f"Image file not found: {image_path}")
        
        # Open and process image
        image = Image.open(image_path)
        
        # Convert to RGB if necessary
        if image.mode != 'RGB':
            image = image.convert('RGB')
        
        # Extract text using Tesseract OCR
        # Configure Tesseract for better accuracy
        custom_config = r'--oem 3 --psm 6 -c tessedit_char_whitelist=0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz .,!?;:()'
        
        text = pytesseract.image_to_string(image, config=custom_config)
        
        # Clean up extracted text
        text = text.strip()
        
        # Remove extra whitespace
        text = ' '.join(text.split())
        
        return text
        
    except Exception as e:
        raise Exception(f"OCR extraction failed: {str(e)}")

def main():
    parser = argparse.ArgumentParser(description='Extract text from image using OCR')
    parser.add_argument('image_path', help='Path to the image file')
    parser.add_argument('--config', help='Tesseract configuration', default='--oem 3 --psm 6')
    parser.add_argument('--min-length', type=int, default=10, help='Minimum text length')
    
    args = parser.parse_args()
    
    try:
        # Extract text
        extracted_text = extract_text_from_image(args.image_path)
        
        # Check minimum length
        if len(extracted_text) < args.min_length:
            raise Exception(f"Extracted text too short (minimum {args.min_length} characters)")
        
        # Output the extracted text
        print(extracted_text)
        
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    main()