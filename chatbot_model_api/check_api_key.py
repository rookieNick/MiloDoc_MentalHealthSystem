import requests

API_KEY = ''  # Replace with your actual key
API_URL = 'https://openrouter.ai/api/v1/chat/completions'

headers = {
    'Authorization': f'Bearer {API_KEY}',
    'Content-Type': 'application/json'
}

data = {
    "model": "google/gemini-2.0-flash-exp:free",
    "messages": [
        {"role": "system", "content": "You are a medical chatbot. Provide clear, helpful, and empathetic answers."},
        {"role": "user", "content": "What are the symptoms of diabetes?"}
    ],

}

response = requests.post(API_URL, json=data, headers=headers)

print("Status Code:", response.status_code)
print("Response:", response.json())
