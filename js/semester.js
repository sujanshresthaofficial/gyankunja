(() => {
  const semesterData = {
    1: [
      ['BIT101', 'Basics of Information Technology'],
      ['ELX111', 'Basic Electronics'],
      ['BSM111', 'Mathematics I'],
      ['CIT111', 'C Programming I'],
      ['CIT113', 'Workshop'],
      ['CIT115', 'Business Communication Technique']
    ],
    2: [
      ['CIT121', 'C Programming II'],
      ['CIT123', 'Digital Logic'],
      ['BSM121', 'Mathematics II'],
      ['CIT125', 'Web Technology I'],
      ['CIT127', 'Computer Architecture'],
      ['MGT121', 'Principles of Management']
    ],
    3: [
      ['CIT211', 'Object-Oriented Programming'],
      ['CIT213', 'Data Structures and Algorithms'],
      ['CIT215', 'Database Management Systems'],
      ['CIT217', 'Web Technology II'],
      ['STA211', 'Probability and Statistics'],
      ['ECO211', 'Economics']
    ],
    4: [
      ['CIT221', 'Java Programming'],
      ['CIT223', 'Operating Systems'],
      ['CIT225', 'Computer Networks'],
      ['CIT227', 'Software Engineering'],
      ['CIT229', 'Numerical Methods'],
      ['MGT221', 'Organizational Behaviour']
    ],
    5: [
      ['CIT311', 'Python Programming'],
      ['CIT313', 'System Analysis and Design'],
      ['CIT315', 'Information Security'],
      ['CIT317', 'Mobile Application Development'],
      ['CIT319', 'Research Methodology'],
      ['ELE311', 'Elective I']
    ],
    6: [
      ['CIT321', 'Artificial Intelligence'],
      ['CIT323', 'Cloud Computing'],
      ['CIT325', 'Data Mining'],
      ['CIT327', 'Human Computer Interaction'],
      ['CIT329', 'Project Management'],
      ['ELE321', 'Elective II']
    ],
    7: [
      ['CIT411', 'Machine Learning'],
      ['CIT413', 'Internet of Things'],
      ['CIT415', 'Enterprise Application Development'],
      ['CIT417', 'Cyber Law and Ethics'],
      ['ELE411', 'Elective III'],
      ['PRJ411', 'Major Project I']
    ],
    8: [
      ['CIT421', 'Big Data Analytics'],
      ['CIT423', 'IT Governance'],
      ['ELE421', 'Elective IV'],
      ['INT421', 'Internship'],
      ['PRJ421', 'Major Project II'],
      ['SEM421', 'Seminar']
    ]
  };

  const page = document.body.dataset.semester;
  const number = Number(page || new URLSearchParams(location.search).get('semester') || 1);
  const roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'][number - 1];
  const courses = semesterData[number] || semesterData[1];

  document.querySelectorAll('[data-semester-title]').forEach(el => el.textContent = `Semester ${roman}`);
  document.querySelectorAll('[data-semester-number]').forEach(el => el.textContent = roman);

  const grid = document.querySelector('#courseGrid');
  const render = (list) => {
    if (!grid) return;
    grid.innerHTML = list.map(([code, title]) => `
      <article class="course-card" data-course="${code} ${title}">
        <div class="course-head">
          <small>${code}</small>
          <h3>${title}</h3>
        </div>
        <div class="course-resources">
          <button class="resource-btn" data-resource="Syllabus">▣ Syllabus</button>
          <button class="resource-btn" data-resource="Theory Notes">▤ Theory Notes</button>
          <button class="resource-btn" data-resource="Lab Manuals">⌘ Lab Manuals</button>
          <button class="resource-btn" data-resource="Question Bank">? Question Bank</button>
          <button class="resource-btn" data-resource="Books">▥ Books</button>
          <button class="resource-btn" data-resource="References">↗ References</button>
        </div>
      </article>`).join('');

    grid.querySelectorAll('.resource-btn').forEach(btn => btn.addEventListener('click', () => {
      const title = btn.closest('.course-card').querySelector('h3').textContent;
      Gyankunja.toast(`${btn.dataset.resource} for ${title} will load from the database later.`);
    }));
  };
  render(courses);

  const search = document.querySelector('#courseSearch');
  if (search) search.addEventListener('input', () => {
    const term = search.value.toLowerCase();
    render(courses.filter(course => course.join(' ').toLowerCase().includes(term)));
  });

  const select = document.querySelector('#semesterSelect');
  if (select) {
    select.value = String(number);
    select.addEventListener('change', () => location.href = `semester${select.value}.html`);
  }
})();
