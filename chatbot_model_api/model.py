import asyncio
import requests
# from langchain.document_loaders import PyPDFLoader
from langchain_community.document_loaders import PyPDFLoader, DirectoryLoader
# from langchain import PromptTemplate
from langchain_core.prompts import PromptTemplate
# from langchain.embeddings import HuggingFaceEmbeddings
# from langchain_community.embeddings import HuggingFaceEmbeddings
from langchain_huggingface import HuggingFaceEmbeddings

# from langchain.vectorstores import FAISS
# from langchain.llms import CTransformers
from langchain_community.vectorstores import FAISS
from langchain_community.llms import CTransformers

from langchain.chains import RetrievalQA
import chainlit as cl
    
import re

DB_FAISS_PATH = 'vectorstores/db_faiss'

# OpenRouter API Key (Replace with your actual key)
API_KEY = ''
API_URL = 'https://openrouter.ai/api/v1/chat/completions'



# custom_prompt_template = """Use the following pieces of information to answer the user's question.
# If you don't know the answer, just say that you don't know, don't try to make up an answer.

# Context: {context}
# Question: {question}

# Only return the helpful answer below and nothing else.
# Helpful answer:
# """

custom_prompt_template = """You are a warm and caring medical assistant chatbot, dedicated to providing empathetic and supportive responses.  
Your goal is to help users feel heard, understood, and comforted while offering practical and thoughtful advice.  

Always respond with kindness, reassurance, and clear guidance. If a question is beyond your expertise, gently encourage the user to seek professional medical advice.  

Context: {context}  
Question: {question}  

Your response should be gentle, encouraging, and easy to understand.
"""




def set_custom_prompt():
    """
    Prompt template for QA retrieval for each vectorstore
    """
    prompt = PromptTemplate(template=custom_prompt_template,
                            input_variables=['context', 'question'])
    return prompt

# Function to query DeepSeek API
async def query(prompt):
    headers = {
        'Authorization': f'Bearer {API_KEY}',
        'Content-Type': 'application/json'
    }
    data = {
        "model": "cognitivecomputations/dolphin3.0-r1-mistral-24b:free",
        "messages": [{"role": "user", "content": prompt}]
    }

    response = requests.post(API_URL, json=data, headers=headers)

    if response.status_code == 200:
        # Print the entire response to understand its structure
        print("Raw API Response:", response.json())

        # Try extracting the expected chatbot response
        try:
            return response.json()["choices"][0]["message"]["content"]
        except KeyError:
            return "Error: Unexpected response format from API."
    else:
        return f"Error: {response.status_code}, {response.text}"

# Load FAISS Vectorstore
async def load_retriever():
    embeddings = HuggingFaceEmbeddings(model_name="sentence-transformers/all-MiniLM-L6-v2",
                                       model_kwargs={'device': 'cpu'})
    return FAISS.load_local(DB_FAISS_PATH, embeddings)

# Chainlit Startup
@cl.on_chat_start
async def start():
    retriever = await load_retriever()
    cl.user_session.set("retriever", retriever)

@cl.on_message
async def main(message):
    retriever = cl.user_session.get("retriever")

    # Retrieve the most relevant context from FAISS
    docs = retriever.similarity_search(message.content, k=2)
    context = "\n".join([doc.page_content for doc in docs])


    sources = set()  # Use a set to avoid duplicate sources
    
    for doc in docs:
        source_name = doc.metadata.get("source", "Unknown Source")  # Filename
        page_number = doc.metadata.get("page", "Unknown Page")  # Page number
        sources.add(f"{source_name} (Page {page_number})")



    # Format the prompt
    prompt = custom_prompt_template.format(context=context, question=message.content)

    # Get response from DeepSeek API
    answer = await query(prompt)


    # Append sources
    if sources:
        answer += f"\n\n📖 **Sources:**\n" + "\n".join(sources)

    # Extract only the source filenames
    # source_files = {doc.metadata.get('source', 'Unknown') for doc in docs}
    # if source_files:
    #     answer += f"\nSources: " + ", ".join(source_files)

    # await cl.Message(content=answer).send()

    # Remove <think>...</think> content using regex
    # answer = re.sub(r"<think>.*?</think>", "", answer, flags=re.DOTALL).strip()

    await cl.Message(content=answer).send()

if __name__ == "__main__":
    asyncio.run(cl.main())