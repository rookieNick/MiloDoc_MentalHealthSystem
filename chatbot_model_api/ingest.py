import json
import os
from tqdm import tqdm
from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain_community.document_loaders import PyPDFLoader, DirectoryLoader
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_community.vectorstores import FAISS

DATA_PATH = "data/"
DB_FAISS_PATH = "vectorstores/db_faiss"
PROGRESS_FILE = os.path.join(os.path.dirname(__file__), "progress.json")

def update_progress(message, percent):
    with open(PROGRESS_FILE, "w") as f:
        json.dump({"message": message, "percent": percent}, f)

def create_vector_db():
    # Step 1: Loading PDFs
    print ("Loading PDF documents...")
    update_progress("Loading PDF documents...", 5)
    loader = DirectoryLoader(DATA_PATH, glob='*.pdf', loader_cls=PyPDFLoader)
    documents = loader.load()
    update_progress(f"Loaded {len(documents)} documents.", 15)

    # Step 2: Splitting documents into chunks
    print ("Splitting documents into chunks...")
    update_progress("Splitting documents into chunks...", 25)
    text_splitter = RecursiveCharacterTextSplitter(chunk_size=500, chunk_overlap=50)

    texts = []
    for doc in tqdm(documents, desc="Processing PDFs"):
        texts.extend(text_splitter.split_documents([doc]))
        update_progress(f"Split {len(texts)} text chunks.", 45)

    # Step 3: Generating embeddings
    print ("Generating embeddings...")
    update_progress("Generating embeddings...", 60)
    embeddings = HuggingFaceEmbeddings(model_name="sentence-transformers/all-MiniLM-L6-v2", model_kwargs={'device': 'cpu'})

    # Step 4: Creating FAISS vector store
    print ("Creating FAISS vector store...")
    update_progress("Creating FAISS vector store...", 70)
    batch_size = 1000
    db = None
    for i in tqdm(range(0, len(texts), batch_size), desc="Processing FAISS batches"):
        batch_texts = texts[i:i+batch_size]
        if db is None:
            db = FAISS.from_documents(batch_texts, embeddings)
        else:
            db.add_documents(batch_texts)

    # Step 5: Saving FAISS database
    print ("FAISS vector store created...")
    update_progress("FAISS vector store created.", 90)
    db.save_local(DB_FAISS_PATH)

    # Final message
    print ("Vector store saved. Process complete...")
    update_progress(f"Vector store saved. Process complete!", 100)

if __name__ == "__main__":
    print("Starting ingestion process...")
    create_vector_db()
    print("Done! Your vector database is ready.")
