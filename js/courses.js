window.GyankunjaCourses = {
    1: [
        ['ELX 111', 'Basic Electronics'],
        ['BSM 111', 'Mathematics I'],
        ['CIT 111', 'Computer Programming I (C)'],
        ['CIT 112', 'Basics of IT'],
        ['CIT 113', 'Workshop: Problem Solving and Logic'],
        ['BCT 111', 'Business Communication Techniques'],
        ['ALL', 'All Subjects']
    ],
    2: [
        ['CIT 121', 'Discrete Mathematics'],
        ['CIT 122', 'Computer Programming II (Java)'],
        ['BSM 123', 'Mathematics II'],
        ['ELX 121', 'Digital Logic'],
        ['CIT 123', 'Web Technology I'],
        ['CIT 124', 'Project I'],
        ['ALL', 'All Subjects']
    ],
    3: [
        ['CIT 211', 'Web Technology II'],
        ['ELX 211', 'Microprocessor and Computer Architecture'],
        ['CIT 212', 'Software Engineering'],
        ['CIT 213', 'Database Management System (DBMS)'],
        ['CIT 214', 'Data Structures and Algorithms (DSA)'],
        ['BCT 211', 'Principles of Organization and Management'],
        ['ALL', 'All Subjects']
    ],
    4: [
        ['CIT 221', 'Operating Systems'],
        ['CIT 222', 'Management Information System'],
        ['CIT 223', 'Data Communication and Computer Network'],
        ['BSM 221', 'Fundamentals of Probability and Statistics'],
        ['CIT 224', 'Computer Graphics Technology'],
        ['CIT 225', 'Project II'],
        ['ALL', 'All Subjects']
    ],
    5: [
        ['CIT 311', 'Mobile Application Development'],
        ['BCT 311', 'Economics'],
        ['CIT 312', 'Object Oriented Analysis and Design with UML'],
        ['BSM 311', 'Numerical Methods'],
        ['BCT 312', 'Research Methodology'],
        ['BCT 313', 'Technocal Proposal Writing'],
        ['ALL', 'All Subjects']
    ],
    6: [
        ['BCT 321', 'IT Project Management'],
        ['CIT 321', 'Human Computer Interface and UI Design'],
        ['CIT 322', 'Digital Forensic Security Technologies'],
        ['CIT 323', 'Artifical Intelligence'],
        ['BCT 322', 'Financial Accounting'],
        ['CIT 324', 'Project III'],
        ['ALL', 'All Subjects']
    ],
    7: [
        ['CIT 411', 'Data Mining and Warehousing'],
        ['CIT 412', 'Software Development and Operations (DevOps)'],
        ['BCT 411', 'Technology Entrepreneurship'],
        ['CIT 413', 'Cloud Computing'],
        ['CIT 414', 'Wireless Communication Systems'],
        ['ALL', 'All Subjects']
    ],
    8: [
        ['CIT 421', 'Big Data Technologies'],
        ['BCT 421', 'Society, IT and Law'],
        ['CIT 422', 'Internship'],
        ['', 'Elective 2'],
        ['CIT 423', 'Project IV'],
        ['ALL', 'All Subjects']
    ]
};

window.GyankunjaRoman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];

// Value saved in the database `subject` column, e.g. "CIT111 - C Programming I".
window.GyankunjaSubjectValue = (code, title) => `${code} - ${title}`;
window.GyankunjaCategories = ['Syllabus', 'Theory Notes', 'Lab Manuals', 'Question Bank', 'Books', 'References'];