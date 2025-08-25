import asyncio
import requests
from fastapi import FastAPI
from pydantic import BaseModel
from langchain_core.prompts import PromptTemplate
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_community.vectorstores import FAISS
import re
from fastapi.middleware.cors import CORSMiddleware

from nlp_suicidal.detect_suicidal import SuicideDetector
from groq import Groq


# Initialize the detector
detector = SuicideDetector()


app = FastAPI()


# Allow all origins for development (change "*" to specific domains in production)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Or ["http://localhost:8080"] if your frontend is on a different port
    allow_credentials=True,
    allow_methods=["*"],  # This allows OPTIONS, POST, GET, etc.
    allow_headers=["*"],
)

DB_FAISS_PATH = "vectorstores/db_faiss"

api_key = ""
client = Groq(api_key=api_key)





class ChatRequest(BaseModel):
    message: str
    memory: str   # This will contain the memory data


async def query_groq(prompt: str):
    """
    Function to query the Llama model via the Groq API client with error handling.
    """
    try:
        # Request completion from the Llama model
        completion = client.chat.completions.create(
            model="meta-llama/llama-4-scout-17b-16e-instruct",
            messages=[{"role": "user", "content": prompt}],
            temperature=1,
            top_p=1,
            stream=False,
        )

        # Check if the response contains expected data
        if len(completion.choices[0].message.content) > 0:
            return completion.choices[0].message.content
        else:
            # If no content is found in the response, return an error message
            return "Error: No valid response content in API result."

    except Exception as e:
        # Log the exception error (for debugging purposes)
        print(f"Error occurred while querying Llama: {e}")
        return f"Error: {str(e)}"

async def load_retriever():
    embeddings = HuggingFaceEmbeddings(
        model_name="sentence-transformers/all-MiniLM-L6-v2",
        model_kwargs={"device": "cpu"},
    )
    # return FAISS.load_local(DB_FAISS_PATH, embeddings)
    return FAISS.load_local(DB_FAISS_PATH, embeddings, allow_dangerous_deserialization=True)

retriever = None

@app.on_event("startup")
async def startup_event():
    global retriever
    retriever = await load_retriever()

@app.post("/chat")
async def chat(request: ChatRequest):
    global retriever
    if retriever is None:
        return {"error": "Retriever not initialized"}

    # Get relevant context from vector store
    docs = retriever.similarity_search(request.message, k=2)
    context = "\n".join([doc.page_content for doc in docs])

    # Collect source info (file name + page number)
    sources = set()
    for doc in docs:
        source_name = doc.metadata.get("source", "Unknown Source")
        page_number = doc.metadata.get("page", "Unknown Page")
        sources.add(f"{source_name} (Page {page_number})")

    # print("🟢 Received Chat Request:")
    # print(f"Message: {request.message}")
    # print(f"Memory: {request.memory}")
    
    # Build the prompt with memory included
    prompt_template = """
    You've been provided with trustworthy medical information from healthcare PDFs.  

    Medical Context (from trusted medical PDFs):
    {context}

    Here's some context from previous journals and conversations to help you understand the user better:  
    {memory}  

    You're a warm, friendly, and caring companion who genuinely listens and supports the user.  
    Your tone is natural, conversational, and encouraging—just like a close friend chatting.  

    ### **Important Instruction:**  
    - If the user shares an emotion, **acknowledge it** and ask them to elaborate.  
    - If they mention an event, **ask about details or feelings** related to it.  
    - If they previously shared a struggle (based on the journals and conversations above), **check in on how it turned out**.  
    - If patient **current** message expresses suicidal thoughts or intentions, respond with empathy and provide them with this link to book consultation with our doctors (provide exactly with the html tag) : <a href="http://localhost:8000/patient/schedule.php">Book Consultation</a>
    - If patient message does not consist of suicidal thoughts, do not provide them the link

    Your response should be **short, engaging, and emotionally supportive** and **in english**.  

    Patient: {question}  
    """

    
    prompt = prompt_template.format(
        context=context,
        memory=request.memory,
        question=request.message
    )

    # 🛑 REMOVE <think>...</think> FROM THE PROMPT
    # prompt = re.sub(r'<think>.*?</think>', '', prompt, flags=re.DOTALL).strip()


    print("\n🟢 Final Generated Prompt:")
    print(prompt)

    
    answer = await query_groq(prompt)

    print("\n🟢 OpenRouter Response:")
    print(answer)

    # Directly remove <think>...</think> blocks inline
    answer = re.sub(r'<think>.*?</think>', '', answer, flags=re.DOTALL).strip()

    print("\n🟢 Filtered Response (without <think>):")
    print(answer)

    # Analyze single text
    suicidal_result = detector.analyze_text(request.message)

    print("\n🟢 Suicide Detection Result:")
    print(suicidal_result)

    # ✅ Append sources to the answer
    if sources:
        answer += "\n\n📖 **Sources used:**\n" + "\n".join(sorted(sources))

    return {
        "response": answer,
        "user_message": request.message,
        "is_suicidal": suicidal_result['is_suicidal'],
        "confidence": suicidal_result['confidence'],
    }



@app.post("/summarise")
async def summarise(request: ChatRequest):
    """
    This endpoint receives the conversation text, builds a prompt instructing the model to summarize
    it concisely and empathetically, and returns the generated summary.
    """
    conversation_text = request.message

    # Build a custom summarization prompt using the conversation text.
    summarization_prompt = (
        "Summarize the following conversation between the patient (user) and the medical chatbot as a concise journal entry. "
        "Focus on capturing the patient's feelings, concerns, and the key points of the discussion in a compassionate tone. "
        "Do not include any extraneous text, introductory phrases, or commentary. Your response should start directly with the summary.\n\n"
        f"{conversation_text}\n\nSummary:"
    )

    # Call your existing model API via query_openrouter with the custom summarization prompt.
    summary = await query_groq(summarization_prompt)

    summary = re.sub(r'<think>.*?</think>', '', summary, flags=re.DOTALL).strip()
    print("\n🟢 Filtered summary (without <think>):")
    print(summary)

    return {"summary": summary}




# 🚀 New Endpoint for Chat Analysis
@app.post("/analyze_chat_report")
async def analyze_chat_report(request: ChatRequest):

    conversation_text = request.message

    analysis_prompt = (
    f"Conversation:\n{conversation_text}\n\n"
    "Analyze the conversation and generate a mental health assessment with scores between 1-100. "
    "Return the response strictly without markdown formatting or extra characters (MUST BE IN RAW TEXT). "
    "for strings, encode in double quote "" "
    '"overall_score": (1-100)\n'
    '"sentiment_score": (1-100)\n'
    '"stress_level": (1-100)\n'
    '"anxiety_level": (1-100)\n'
    '"depression_risk": (1-100)\n'
    '"overall_score_reason": "Brief explanation of the overall score."\n'
    '"sentiment_score_reason": "Brief explanation of the sentiment score."\n'
    '"stress_level_reason": "Brief explanation of the stress level."\n'
    '"anxiety_level_reason": "Brief explanation of the anxiety level."\n'
    '"depression_risk_reason": "Brief explanation of the depression risk."\n'
)



    report = await query_groq(analysis_prompt)

    report = re.sub(r'<think>.*?</think>', '', report, flags=re.DOTALL).strip()
    print("\n🟢 Filtered report (without <think>):")
    print(report)
    
    return {"report": report}




@app.post("/journal_feedback")
async def journal_feedback(request: ChatRequest):
    """
    This endpoint receives the original AI-generated summary and the user's feedback,
    and asks the AI to regenerate a more accurate journal entry based on that feedback.
    """
    journal_content = request.memory.strip()
    user_feedback = request.message.strip()

    # Build the prompt
    refinement_prompt = (
        "You are a mental health chatbot that creates empathetic, clear, and emotionally intelligent journal summaries. "
        "Below is the original summary you previously generated, followed by the user's feedback pointing out what was missing or inaccurate.\n\n"
        f"Original Summary:\n{journal_content}\n\n"
        f"User Feedback:\n{user_feedback}\n\n"
        "Please rewrite the journal entry to better reflect the user's original experience and concerns. "
        "Make sure to keep the tone compassionate and include important details the user felt were missing. "
        "Return the response strictly without markdown formatting or extra characters (MUST BE IN RAW TEXT)."
        '"refined_journal":'
    )

    # Get the improved summary
    refined_journal = await query_groq(refinement_prompt)

    print("Generated prompt refine journal: ")
    print(refined_journal)

    refined_journal = re.sub(r'<think>.*?</think>', '', refined_journal, flags=re.DOTALL).strip()
    print("\n🟢 Filtered refined (without <think>):")
    print(refined_journal)

    

    return {"refined_journal": refined_journal}
