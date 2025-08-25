import sklearn
import pickle
import nltk
from nltk.corpus import stopwords
from nltk.stem import PorterStemmer
import os

# Ensure stopwords are downloaded
try:
    stop_words = stopwords.words('english')
except LookupError:
    nltk.download('stopwords')
    stop_words = stopwords.words('english')



# Get the current script directory
current_dir = os.path.dirname(os.path.abspath(__file__))

model_path = os.path.join(current_dir,'tfidf.pkl')


stop_words = stopwords.words('english') 
# better file handling needed
with open(model_path, 'rb') as f:
    tfidf = pickle.load(f)

def preprocess(inp):
    inp = inp.lower()
    inp = inp.replace(r'[^\w\s]+', '')
    inp = [word for word in inp.split() if word not in (stop_words)]

    ps = PorterStemmer()
    inp = ' '.join([ps.stem(i) for i in inp])
    inputToModel = tfidf.transform([inp]).toarray()
    return inputToModel