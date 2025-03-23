import mysql.connector
from faker import Faker
import random
from datetime import datetime, timedelta
import re
import json

course_class_map = []

# Database Configuration
DB_CONFIG = {
    "host": "localhost",  # Replace with your database host
    "user": "root",  # Replace with your username
    "password": "",  # Replace with your password
    "database": "elearning"  # Replace with your database name
}

# Configuration for number of records
CONFIG = {
    "num_admins": 3, # Total number of users to generate
    "num_students": 200,  # Total number of students to generate
    "num_parents": 100,  # Total number of parents to generate
    "num_teachers": 50,  # Total number of teachers to generate
    "num_class_student_work": 2,  # Total number of student works to generate
    "num_of_courses_teacher_teaches": 1,
    "min_students_in_class": 20,
    "max_students_in_class": 30
}

# Connect to MySQL
conn = mysql.connector.connect(**DB_CONFIG)
cursor = conn.cursor()

fake = Faker()

# Function to insert a user
def insert_user(full_name, email, password_hash, role, phone_number, address, profile_picture):
    sql = """
    INSERT INTO users (full_name, email, password_hash, role, phone_number, address, profile_picture)
    VALUES (%s, %s, %s, %s, %s, %s, %s)
    """
    data = (full_name, email, password_hash, role, phone_number, address, profile_picture)
    cursor.execute(sql, data)
    conn.commit()
    return cursor.lastrowid  # Return the new user's ID

# Function to insert a student
def insert_student(user_id):
    sql = """
    INSERT INTO students (user_id, date_of_birth, grade_level, emergency_contact)
    VALUES (%s, %s, %s, %s)
    """
    data = (
        user_id,
        fake.date_of_birth(minimum_age=6, maximum_age=18),
        random.choice(['1st Grade', '2nd Grade', '3rd Grade', '4th Grade', '5th Grade', '6th Grade', '7th Grade', '8th Grade', '9th Grade', '10th Grade', '11th Grade', '12th Grade']),
        generate_phone_number()
    )
    cursor.execute(sql, data)
    conn.commit()
    return cursor.lastrowid  # Return the student ID

# Function to insert a admin
def insert_admin(user_id):
    sql = """
    INSERT INTO admins (user_id, access_level)
    VALUES (%s, %s)
    """
    data = (
        user_id,
        random.choice(['superadmin', 'moderator', 'staff'])
    )
    cursor.execute(sql, data)
    conn.commit()
    return cursor.lastrowid # Return the new user's ID

# Function to insert a parent
def insert_parent(user_id):
    sql = """
    INSERT INTO parents (user_id, occupation, notes)
    VALUES (%s, %s, %s)
    """
    data = (
        user_id,
        fake.job(),
        fake.text()
    )
    cursor.execute(sql, data)
    conn.commit()
    return cursor.lastrowid  # Return the parent ID

# Function to insert a teacher
def insert_teacher(user_id):
    sql = """
    INSERT INTO teachers (user_id, subject_specialization, qualification, experience_years, bio)
    VALUES (%s, %s, %s, %s, %s)
    """
    data = (
        user_id,
        random.choice(['Math', 'Science', 'English', 'History', 'Art']),
        random.choice(['B.Ed', 'M.Ed', 'Ph.D']),
        random.randint(1, 30),
        fake.paragraph()
    )
    cursor.execute(sql, data)
    conn.commit()
    return cursor.lastrowid  # Return the teacher ID

# Function to insert a course
def insert_course(teacher_id, title, description, subject):
    sql = """
    INSERT INTO courses (title, description, subject, teacher_id, start_date, end_date)
    VALUES (%s, %s, %s, %s, %s, %s)
    """
    data = (
        title,  
        description,  # Course description
        subject,  # Subject
        teacher_id,  # Valid teacher_id from the inserted teacher
        fake.date_this_year(),  # Start date
        fake.date_this_year() + timedelta(days=random.randint(30, 180))  # End date
    )
    cursor.execute(sql, data)
    conn.commit()
    return cursor.lastrowid  # Return the course ID

# Function to insert a class
def insert_class(course_id, teacher_id, classes):
    sql = """
    INSERT INTO class_section (course_id, teacher_id, class_name, description, schedule, start_date, end_date)
    VALUES (%s, %s, %s, %s, %s, %s, %s)
    """
    data = (
        course_id,
        teacher_id,
        classes['class_name'],  # Class name (could be replaced by meaningful names)
        classes['class_description'],  # Class description
        classes['schedule'],
        fake.date_this_year(),
        fake.date_this_year() + timedelta(days=random.randint(30, 180))  # End date
    )
    cursor.execute(sql, data)
    conn.commit()
    return cursor.lastrowid  # Return the class ID

# Function to link students with classes
def link_student_to_class(student_id, class_id):
    sql = """
    INSERT INTO section_enrollment (class_id, student_id, enrollment_date)
    VALUES (%s, %s, %s)
    """
    data = (
        class_id,
        student_id,
        fake.date_this_year()
    )
    cursor.execute(sql, data)
    conn.commit()

# Function to insert subscription for a parent
def insert_subscription(parent_id):
    sql = """
    INSERT INTO subscriptions (parent_id, subscription_type, amount, payment_method, start_date, end_date)
    VALUES (%s, %s, %s, %s, %s, %s)
    """
    data = (
        parent_id,
        random.choice(['monthly', 'quarterly', 'yearly']),
        random.uniform(50, 500),
        random.choice(['credit_card', 'bank_transfer', 'paypal', 'cash']),
        fake.date_this_year(),
        fake.date_this_year() + timedelta(days=random.randint(30, 365))  # End date
    )
    cursor.execute(sql, data)
    conn.commit()

# Function to create a parent-student relationship
def create_parent_student_relation(parent_id, student_id):
    sql = """
    INSERT INTO parent_student_relation (parent_id, student_id, relationship)
    VALUES (%s, %s, %s)
    """
    data = (
        parent_id,
        student_id,
        random.choice(['Father', 'Mother', 'Guardian'])
    )
    cursor.execute(sql, data)
    conn.commit()

# Function to insert student courses
def insert_student_course(student_id, course_id):
    sql = """
    INSERT IGNORE INTO course_enrollment (student_id, course_id, enrollment_date)
    VALUES (%s, %s, %s)
    """
    data = (
        student_id,
        course_id,
        fake.date_this_year()
    )
    cursor.execute(sql, data)
    conn.commit()

# Function to insert student work for a class
def insert_class_student_work(class_student_id):
    sql = """
    INSERT INTO class_assessments (class_student_id, assignment_name, score, max_score, feedback)
    VALUES (%s, %s, %s, %s, %s)
    """
    data = (
        class_student_id,
        random.choice(['Midterm', 'Assignment 1', 'Assignemnt 2', 'Pop Quiz', 'Final Exam', 'Activity']),  # Assignment name (could be replaced by meaningful names)
        random.uniform(50, 100),  # Score between 50 and 100
        100,  # Max score
        fake.text()  # Feedback
    )
    cursor.execute(sql, data)
    conn.commit()

def generate_phone_number():
    return f"+1-{random.randint(100, 999)}-{random.randint(100, 999)}-{random.randint(1000, 9999)}"


def generate_email(full_name, domains=None):
    if domains is None:
        # Default list of domains if none provided
        domains = ["gmail.com", "hotmail.com"]
    domain = random.choice(domains)
    clean_name = re.sub(r'[^a-zA-Z\s]', '', full_name).lower().strip()
    name_parts = clean_name.split()
    
    if len(name_parts) >= 2:
        email = f"{name_parts[0]}.{name_parts[-1]}@{domain}"
    else:
        email = f"{name_parts[0]}.{name_parts[0]}@{domain}"

    if (check_user_exists(email)):
        random_number = random.randint(100, 999)
        if len(name_parts) >= 2:
            email = f"{name_parts[0]}.{name_parts[-1]}.{random_number}@{domain}"
        else:
            email = f"{name_parts[0]}.{name_parts[0]}.{random_number}@{domain}"
    
    return email

def get_random_class(course):
    # If the course is found and it has classes, select one randomly
    if course and 'classes' in course and course['classes']:
        return random.choice(course['classes'])
    else:
        return None 


def get_value(key):
    for k, v in course_class_map:
        if k == key:
            return v
    return None  

def is_valid_index(array, index):
    if 0 <= index < len(array):
        return True
    else:
        return False

def check_user_exists(email):
    query = "SELECT id FROM users WHERE email = %s"
    cursor.execute(query, (email,))
    # Fetch the result
    result = cursor.fetchone()
    if result:
        return True
    else:
        return False

# Function to generate random data for users with configurable numbers
def generate_dummy_data():
    student_ids = []
    parent_ids = []
    teacher_ids = []
    course_ids = []
    class_ids = []
    teacher_class_registration = []
    # Insert Users (Parents, Students, Teachers, Admins)
    for _ in range(CONFIG["num_parents"]):
        name = fake.name()
        email = generate_email(name)
        password_hash = fake.sha256()
        phone_number = generate_phone_number()
        address = fake.address()
        url = fake.image_url()
        user_id = insert_user(name, email, password_hash, 'PARENT', phone_number, address, url)
        parent_id = insert_parent(user_id)
        parent_ids.append(parent_id)

    print(f"Successfully Inserted Parents: {CONFIG["num_parents"]}")

    for _ in range(CONFIG["num_students"]):
        name = fake.name()
        email = generate_email(name)
        password_hash = fake.sha256()
        phone_number = generate_phone_number()
        address = fake.address()
        url = fake.image_url()
        user_id = insert_user(name, email, password_hash, 'STUDENT', phone_number, address, url)
        student_id = insert_student(user_id)
        student_ids.append(student_id)

    print(f"✅ Successfully Inserted Students: {CONFIG["num_students"]}")

    for _ in range(CONFIG["num_teachers"]):
        name = fake.name()
        email = generate_email(name)
        password_hash = fake.sha256()
        phone_number = generate_phone_number()
        address = fake.address()
        url = fake.image_url()
        user_id = insert_user(name, email, password_hash, 'TEACHER', phone_number, address, url)
        teacher_id = insert_teacher(user_id)
        teacher_ids.append(teacher_id)
    
    print(f"✅ Successfully Inserted Teachers: {CONFIG["num_teachers"]}")

    for _ in range(CONFIG["num_admins"]):
        name = fake.name()
        email = generate_email(name)
        password_hash = fake.sha256()
        phone_number = generate_phone_number()
        address = fake.address()
        url = fake.image_url()
        user_id = insert_user(name, email, password_hash, 'ADMIN', phone_number, address, url)
        insert_admin(user_id)

    print(f"✅ Successfully Inserted Admins: {CONFIG["num_admins"]}")

    with open('generated_courses.json', 'r') as file:
        courses_json = json.load(file)

    # Insert Courses and Link Teachers
    for teacher_id in teacher_ids:
        temp_courses = courses_json
        random.shuffle(temp_courses)
        counter = 0
        for course in temp_courses:
            course_id = insert_course(teacher_id, course['title'], course['description'], course['subject'])
            course_class_map.append((course_id, course))
            course_ids.append(course_id)
            counter = counter + 1
            if (counter == CONFIG["num_of_courses_teacher_teaches"]):
                counter = 0
                break
    
    print(f"✅ Successfully Inserted Courses and Linked Teachers")

    # Create Parent-Student Relations
    for student_id in student_ids:
        parent_id = random.choice(parent_ids)
        create_parent_student_relation(parent_id, student_id)
    
    print(f"✅ Successfully Create Parent-Student Relations ")
    
    counter = 0
    for course_id in course_ids:
        course = get_value(course_id)
        for classes in course['classes']:
            if (is_valid_index(teacher_ids, counter) == False):
                counter = 0
            else:
                teacher_id = teacher_ids[counter]
                class_id = insert_class(course_id, teacher_id, classes)
                class_ids.append(class_id)
                counter = counter + 1                    
                temp_student_list = student_ids
                random.shuffle(temp_student_list)
                for i in range(random.randint(CONFIG["min_students_in_class"], CONFIG["max_students_in_class"],)):
                    if (is_valid_index(temp_student_list, i)):
                        insert_student_course(temp_student_list[i], course_id)
                        link_student_to_class(temp_student_list[i], class_id)
            
    
    print(f"✅ Successfully Insert Classes and Link Students to Classes")


    sql = "SELECT id FROM section_enrollment"
    cursor.execute(sql)
    results = cursor.fetchall()
    if results:
        for result in results:
            class_student_id = result[0]
            
            if class_student_id:
                # Insert class student work for each student
                for _ in range(CONFIG["num_class_student_work"]):
                    insert_class_student_work(class_student_id)

    print(f"✅ Successfully Insert Class Student Work")

    # Insert Subscriptions
    for parent_id in parent_ids:
        insert_subscription(parent_id)

    print(f"✅ Successfully Insert Subscriptions")

# Call function to generate dummy data
generate_dummy_data()

# Close the connection
cursor.close()
conn.close()
