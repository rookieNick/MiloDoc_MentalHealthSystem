from groq import Groq

# Replace with your actual API key
api_key = ""

client = Groq(api_key=api_key)
completion = client.chat.completions.create(
    model="qwen-qwq-32b",
    messages=[
        {"role": "user", "content": "hi"},
    ],
    temperature=1,
    max_tokens=1024,
    top_p=1,
    stream=False,
)

print(completion.choices[0].message.content)
# for chunk in completion:
#     print(chunk.choices[0].delta.content or "", end="")
