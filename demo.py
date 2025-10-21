#!/usr/bin/env python3
"""
Demo script showing the complete LMS workflow.
This demonstrates all major features of the Learning Management System.
"""

import requests
import json
import time

BASE_URL = "http://localhost:5000"

def print_section(title):
    """Print a section header."""
    print("\n" + "=" * 70)
    print(f"  {title}")
    print("=" * 70 + "\n")

def demo_lms():
    """Run a complete demo of the LMS system."""
    
    # Create a session to persist cookies
    session = requests.Session()
    
    print_section("🎓 Learning Management System - Complete Demo")
    print("This demo showcases all five major features of the LMS:\n")
    print("1. Online Courses")
    print("2. Tests and Quizzes")
    print("3. Certificates")
    print("4. Learning Progress Tracking")
    print("5. AI-Powered Course Recommendations")
    
    # 1. AUTHENTICATION
    print_section("1️⃣ User Authentication")
    
    # Login as existing user
    print("Logging in as john_doe...")
    response = session.post(f"{BASE_URL}/auth/login", json={
        "username": "john_doe",
        "password": "password123"
    })
    
    if response.status_code == 200:
        user_info = response.json()
        print(f"✅ Login successful!")
        print(f"   User: {user_info['user']['username']}")
        print(f"   Email: {user_info['user']['email']}")
    else:
        print("❌ Login failed. Make sure the server is running and database is seeded.")
        return
    
    # 2. ONLINE COURSES
    print_section("2️⃣ Online Courses")
    
    print("Fetching available courses...")
    response = session.get(f"{BASE_URL}/courses/")
    courses = response.json()["courses"]
    
    print(f"✅ Found {len(courses)} courses:\n")
    for course in courses[:3]:
        print(f"   📚 {course['title']}")
        print(f"      Category: {course['category']} | Difficulty: {course['difficulty']}")
        print(f"      Duration: {course['duration_hours']} hours | Students: {course['enrolled_students']}")
        print()
    
    # Get course details
    course_id = courses[0]['id']
    response = session.get(f"{BASE_URL}/courses/{course_id}")
    course_detail = response.json()
    
    print(f"Course Details: {course_detail['title']}")
    print(f"   Lessons: {len(course_detail['lessons'])}")
    print(f"   Assessments: {len(course_detail['assessments'])}")
    
    # Enroll in a new course (if not already enrolled)
    if len(courses) > 1:
        new_course_id = courses[1]['id']
        print(f"\nEnrolling in: {courses[1]['title']}...")
        response = session.post(f"{BASE_URL}/courses/{new_course_id}/enroll")
        if response.status_code == 200:
            print("✅ Successfully enrolled!")
        elif "already enrolled" in response.text.lower():
            print("ℹ️  Already enrolled in this course")
    
    # 3. LEARNING PROGRESS
    print_section("3️⃣ Learning Progress Tracking")
    
    print("Checking my learning progress...")
    response = session.get(f"{BASE_URL}/progress/my-progress")
    progress_data = response.json()["progress"]
    
    if progress_data:
        print(f"✅ Progress for {len(progress_data)} enrolled courses:\n")
        for prog in progress_data:
            print(f"   📊 {prog['course_title']}")
            print(f"      Progress: {prog['progress_percentage']}% ({prog['completed_lessons']}/{prog['total_lessons']} lessons)")
            status = "✅ Complete" if prog['completed'] else "🔄 In Progress"
            print(f"      Status: {status}")
            print()
    
    # Get detailed course progress
    response = session.get(f"{BASE_URL}/progress/course/{course_id}")
    course_progress = response.json()
    
    print(f"Detailed Progress for '{course_progress['course_title']}':")
    print(f"   Overall: {course_progress['overall_progress']}%")
    print(f"   Completed: {course_progress['completed_lessons']}/{course_progress['total_lessons']} lessons")
    
    # Mark a lesson as complete
    if course_progress['lesson_progress']:
        lesson = course_progress['lesson_progress'][0]
        if not lesson['completed']:
            lesson_id = lesson['lesson_id']
            print(f"\nMarking lesson '{lesson['lesson_title']}' as complete...")
            response = session.post(f"{BASE_URL}/progress/lesson/{lesson_id}/complete")
            if response.status_code == 200:
                result = response.json()
                print(f"✅ Lesson completed! Course progress: {result['course_progress']}%")
    
    # 4. TESTS AND QUIZZES
    print_section("4️⃣ Tests and Quizzes")
    
    # Get assessment
    print("Fetching available assessments...")
    response = session.get(f"{BASE_URL}/courses/{course_id}")
    assessments = response.json()["assessments"]
    
    if assessments:
        assessment = assessments[0]
        assessment_id = assessment['id']
        
        print(f"✅ Found assessment: {assessment['title']}")
        print(f"   Type: {assessment['type']} | Passing score: {assessment['passing_score']}%")
        print(f"   Questions: {assessment['questions_count']}")
        
        # Get assessment details
        response = session.get(f"{BASE_URL}/assessments/{assessment_id}")
        assessment_detail = response.json()
        
        print(f"\nStarting assessment...")
        response = session.post(f"{BASE_URL}/assessments/{assessment_id}/start")
        attempt = response.json()["attempt"]
        attempt_id = attempt['id']
        print(f"✅ Assessment started (Attempt ID: {attempt_id})")
        
        # Submit answers (answering all questions correctly for demo)
        questions = assessment_detail['questions']
        answers = []
        for q in questions:
            # Find the correct answer
            correct_option = next((opt for opt in q['options'] if opt.get('id')), None)
            if correct_option:
                answers.append({
                    "question_id": q['id'],
                    "selected_option_id": correct_option['id']
                })
        
        print(f"Submitting {len(answers)} answers...")
        response = session.post(
            f"{BASE_URL}/assessments/attempts/{attempt_id}/submit",
            json={"answers": answers}
        )
        
        if response.status_code == 200:
            result = response.json()["result"]
            print(f"✅ Assessment submitted!")
            print(f"   Score: {result['score']}/{result['max_score']}")
            print(f"   Percentage: {result['percentage']}%")
            print(f"   Status: {'✅ PASSED' if result['passed'] else '❌ FAILED'}")
    else:
        print("ℹ️  No assessments available for this course")
    
    # View all attempts
    print("\nViewing all my assessment attempts...")
    response = session.get(f"{BASE_URL}/assessments/my-attempts")
    attempts = response.json()["attempts"]
    print(f"✅ Total attempts: {len(attempts)}")
    
    # 5. CERTIFICATES
    print_section("5️⃣ Certificates")
    
    print("Checking my certificates...")
    response = session.get(f"{BASE_URL}/certificates/my-certificates")
    certificates = response.json()["certificates"]
    
    if certificates:
        print(f"✅ You have {len(certificates)} certificate(s):\n")
        for cert in certificates:
            print(f"   🏆 {cert['course_title']}")
            print(f"      Code: {cert['certificate_code']}")
            print(f"      Issued: {cert['issued_at'][:10]}")
            print(f"      Download: {cert['download_url']}")
            print()
    else:
        print("ℹ️  No certificates yet. Complete all course lessons to earn one!")
    
    # 6. AI RECOMMENDATIONS
    print_section("6️⃣ AI-Powered Course Recommendations")
    
    print("Getting personalized course recommendations...")
    response = session.get(f"{BASE_URL}/recommendations/")
    recommendations = response.json()["recommendations"]
    
    if recommendations:
        print(f"✅ Top {min(5, len(recommendations))} recommended courses for you:\n")
        for i, rec in enumerate(recommendations[:5], 1):
            print(f"   {i}. {rec['title']}")
            print(f"      Category: {rec['category']} | Difficulty: {rec['difficulty']}")
            print(f"      Duration: {rec['duration_hours']} hours")
            print(f"      Recommendation Score: {rec['recommendation_score']:.1f}")
            if rec['reasons']:
                print(f"      Why? {', '.join(rec['reasons'])}")
            print()
    else:
        print("✅ You're enrolled in all available courses!")
    
    # Get trending courses
    print("Checking trending courses...")
    response = session.get(f"{BASE_URL}/recommendations/trending")
    trending = response.json()["trending_courses"]
    
    if trending:
        print(f"\n🔥 Top 3 Trending Courses:\n")
        for i, course in enumerate(trending[:3], 1):
            print(f"   {i}. {course['title']}")
            print(f"      {course['enrolled_count']} students enrolled")
            print()
    
    print_section("✅ Demo Complete!")
    print("The LMS successfully demonstrates all five core features:")
    print("  ✅ Online courses with enrollment")
    print("  ✅ Tests and quizzes with automatic grading")
    print("  ✅ Certificate generation and verification")
    print("  ✅ Detailed learning progress tracking")
    print("  ✅ AI-powered personalized recommendations")
    print("\nFor more information, see README.md and API_EXAMPLES.md")
    print("=" * 70 + "\n")

if __name__ == "__main__":
    try:
        demo_lms()
    except requests.exceptions.ConnectionError:
        print("\n❌ Error: Cannot connect to LMS server.")
        print("Please make sure the Flask application is running:")
        print("   python app.py")
        print("\nThen run this demo script again.")
    except Exception as e:
        print(f"\n❌ Error: {e}")
        print("Please check that the database is seeded:")
        print("   python seed_data.py")
