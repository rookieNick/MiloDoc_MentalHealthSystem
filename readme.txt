1. extract zip file (RSWG3_BehJingHen_App)

2. after extract the app file click into it and import sql file ("mdoc_database.sql)

3. after that right click the app file and select "open with code"

4. # For activating chatbot model & suicidal detection
   cd chatbot_model_api
   python -m venv venv (to create virtual environment for the project)
   venv/Scripts/activate (to activate the virtual environment)
   pip install -r requirements.txt (to install all the libraries)
   uvicorn app_llama:app --reload --port 8001  (to open 8001 port for chatbot    model api and detect suicidal) 

5. write this "cd website; php -S localhost:8000" at the terminal to run the code

6. For the admin login 
   -Username : admin@mdoc.com
   -Password : Beh@030118

7. For the doctor login 
   -Username : doctorlim0000@gmail.com
   -Password : Beh@030118

8. For the patient login
   -Username : behjinghen@gmail.com
   -Password : Beh@030118

** For the google calendar verification
- You can login with account for doctor in google
Username : doctorlim0000@gmail.com
Password : mdocclinic

- You can register as a member for patient (You) in the login page with the "Sign Up" with your school email address, for example : "lokesv@tarc.edu.my"

- You also can login with the mdoc official email
Username: mdocclinic@gmail.com
Password: mdocclinic

*****After you login with google account you need to login as doctor first to get the access_token before you book a slot as a patient (We demo that both user already login in their device)

 