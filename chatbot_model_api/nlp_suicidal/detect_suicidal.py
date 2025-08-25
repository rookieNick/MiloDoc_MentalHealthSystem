import pickle
import os
import sys
import re
from pathlib import Path
import mysql.connector

# Add the current directory to Python path (nlp_suicidal folder)
current_dir = Path(__file__).parent
sys.path.append(str(current_dir))

# Now import from local utils
from utils.preprocessing import preprocess

class SuicideDetector:
    def __init__(self):
        # Path to model - now relative to current directory
        model_path = os.path.join(current_dir, 'utils', 'best_model.pkl')
        
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"Model file not found at: {model_path}")
        
        with open(model_path, 'rb') as f:
            self.model = pickle.load(f)
        
        print(f'Model loaded successfully from {model_path}')

        # Connect to MySQL database and load keywords
        self.keywords = self.load_keywords_from_db()
        print(f"Loaded {len(self.keywords)} sensitive keywords from database.")


    def load_keywords_from_db(self):
        try:
            conn = mysql.connector.connect(
                host='localhost',
                user='root',
                password='',
                database='mDoc'
            )
            print("[DB] ✅ Connected to the database successfully.")

            cursor = conn.cursor()
            cursor.execute("SELECT keyword FROM sensitive_keywords WHERE status=1 AND keyword IS NOT NULL")
            keywords = [row[0].lower() for row in cursor.fetchall()]
            print(f"[DB] ✅ Loaded {len(keywords)} sensitive keywords.")

            cursor.close()
            conn.close()
            return keywords
        except Exception as e:
            print(f"[DB] ❌ Error connecting to DB or loading keywords: {e}")
            return []



    def analyze_text(self, text):

        lowered = text.lower()

        # Check if any sensitive keyword is in the text
        for keyword in self.keywords:
            if re.search(r'\b' + re.escape(keyword) + r'\b', lowered):
                return {
                    'text': text,
                    'prediction': 'suicide',
                    'confidence': 100.0,
                    'is_suicidal': True
                }

        # Fallback to ML model if no keyword match
        processed_array = preprocess(text)
        prediction = self.model.predict(processed_array)
        probabilities = self.model.predict_proba(processed_array)
        confidence = round(max(probabilities[0]) * 100, 2)

 
        return {
            'text': text,
            'prediction': prediction[0],
            'confidence': confidence,
            'is_suicidal': prediction[0] == 'suicide'
        }

    def analyze_multiple_texts(self, texts):
        return [self.analyze_text(text) for text in texts]
    

def main():
    # Initialize the detector
    detector = SuicideDetector()
    
    print("Suicidal Text Detection System")
    print("=" * 50)
    print("Type a sentence to analyze (or 'quit' to exit)\n")
    
    while True:
        # Get user input
        user_input = input("Enter a sentence: ").strip()
        
        # Exit condition
        if user_input.lower() in ('quit', 'exit', 'q'):
            print("\nExiting program...")
            break
            
        # Skip empty input
        if not user_input:
            print("Please enter some text\n")
            continue
            
        # Analyze the text
        result = detector.analyze_text(user_input)
        
        # Display results with color formatting
        if result['is_suicidal']:
            print(f"\n\033[91m⚠️ WARNING: Potential suicidal content detected!\033[0m")
            print(f"Confidence: \033[91m{result['confidence']}%\033[0m")
        else:
            print(f"\n\033[92m✅ No suicidal content detected\033[0m")
            print(f"Confidence: \033[92m{result['confidence']}%\033[0m")
            
        print(f"Prediction: {result['prediction']}")
        print("-" * 50 + "\n")

if __name__ == "__main__":
    main()