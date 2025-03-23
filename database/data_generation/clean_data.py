import mysql.connector

# Establish a connection to the database
conn = mysql.connector.connect(
    host='localhost',       # e.g., 'localhost' or an IP address
    user='root',   # your MySQL username
    password='',  # your MySQL password
    database='elearning'  # the name of your database
)

# Create a cursor object to interact with the database
cursor = conn.cursor()

# List of DELETE queries to be executed
delete_queries = [
    "DELETE FROM `class_assessments`;",
    "DELETE FROM `section_enrollment`;",
    "DELETE FROM `class_section`;",
    "DELETE FROM `parent_student_relation`;",
    "DELETE FROM `parents`;",
    "DELETE FROM `students`;",
    "DELETE FROM `admins`;",
    "DELETE FROM `teachers`;",
    "DELETE FROM `users`;",
    "DELETE FROM `courses`;"
]

# Table names to match each DELETE query
table_names = [
    "class_assessments",
    "section_enrollment",
    "class_section",
    "parent_student_relation",
    "parents",
    "students",
    "admins",
    "teachers",
    "users",
    "courses"
]

# Execute each DELETE query in sequence and print the number of affected rows
for query, table_name in zip(delete_queries, table_names):
    cursor.execute(query)
    print(f"Deleted {cursor.rowcount} rows from {table_name}")

# Commit the transaction to apply all the DELETE queries
conn.commit()

# Close the cursor and connection
cursor.close()
conn.close()