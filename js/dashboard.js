(() => {
  const user = Gyankunja.getUser();
  if (!user) return;

  const semesterNumber = Math.max(1, ['Semester I','Semester II','Semester III','Semester IV','Semester V','Semester VI','Semester VII','Semester VIII'].indexOf(user.semester) + 1);
  const roman = ['I','II','III','IV','V','VI','VII','VIII'][semesterNumber - 1];
  const firstName = user.name.split(' ')[0];
  const initials = user.name.split(' ').map(part => part[0]).join('').slice(0, 2).toUpperCase();

  const semesterContent = {
    1: {
      notes: [
        ['PDF','Verified','Basics of IT — Complete Unit Notes','BIT101 • 164 views'],
        ['PDF','New','Basic Electronics — Diodes and Transistors','ELX111 • 286 views'],
        ['DOC','Verified','Mathematics I Formula Sheet','BSM111 • 95 views'],
        ['LAB','Popular','C Programming I Lab Manual','CIT111 • 312 views'],
        ['PDF','Verified','Workshop Safety and Tools','CIT113 • 173 views'],
        ['PDF','Saved','Business Communication Quick Revision','CIT115 • 118 views']
      ],
      exams: [['21','JUL','C Programming I','Internal Assessment','10:00 AM'],['25','JUL','Basic Electronics','Lab Practical','1:00 PM'],['29','JUL','Mathematics I','Unit Test','8:00 AM']]
    },
    2: {
      notes: [
        ['PDF','Verified','Exception Handling in C Programming II','CIT121 • 184 views'],
        ['LAB','New','Digital Logic Lab Manual','CIT123 • 241 views'],
        ['DOC','Verified','Mathematics II Formula Sheet','BSM121 • 132 views'],
        ['PDF','Popular','HTML, CSS and JavaScript Notes','CIT125 • 327 views'],
        ['PDF','Verified','Computer Architecture Revision Notes','CIT127 • 159 views'],
        ['PDF','Saved','Principles of Management Summary','MGT121 • 104 views']
      ],
      exams: [['20','JUL','C Programming II','Internal Assessment','10:00 AM'],['24','JUL','Digital Logic','Lab Practical','1:00 PM'],['28','JUL','Mathematics II','Unit Test','8:00 AM']]
    },
    3: {
      notes: [
        ['PDF','Verified','OOP Concepts and Examples','CIT211 • 214 views'],
        ['DOC','New','Data Structures — Stack and Queue','CIT213 • 196 views'],
        ['PDF','Verified','DBMS Normalization Notes','CIT215 • 284 views'],
        ['LAB','Popular','Web Technology II Practical Files','CIT217 • 237 views'],
        ['PDF','Verified','Probability and Statistics Formulas','STA211 • 149 views'],
        ['PDF','Saved','Economics Short Notes','ECO211 • 92 views']
      ],
      exams: [['19','JUL','Object-Oriented Programming','Internal Assessment','9:00 AM'],['23','JUL','Database Management','Practical','12:00 PM'],['27','JUL','Statistics','Unit Test','8:00 AM']]
    },
    4: {
      notes: [
        ['PDF','Verified','Java Programming Complete Notes','CIT221 • 255 views'],
        ['DOC','New','Operating System Process Scheduling','CIT223 • 188 views'],
        ['PDF','Verified','Computer Network Protocols','CIT225 • 279 views'],
        ['PDF','Popular','Software Engineering Models','CIT227 • 221 views'],
        ['LAB','Verified','Numerical Methods Practical Manual','CIT229 • 143 views'],
        ['PDF','Saved','Organizational Behaviour Summary','MGT221 • 87 views']
      ],
      exams: [['18','JUL','Java Programming','Internal Assessment','10:00 AM'],['22','JUL','Computer Networks','Practical','1:00 PM'],['26','JUL','Numerical Methods','Unit Test','8:00 AM']]
    },
    5: {
      notes: [
        ['PDF','Verified','Python Programming Handbook','CIT311 • 301 views'],
        ['DOC','New','System Analysis UML Notes','CIT313 • 169 views'],
        ['PDF','Verified','Information Security Fundamentals','CIT315 • 254 views'],
        ['LAB','Popular','Mobile App Development Practical','CIT317 • 286 views'],
        ['PDF','Verified','Research Methodology Guide','CIT319 • 137 views'],
        ['PDF','Saved','Elective I Study Pack','ELE311 • 81 views']
      ],
      exams: [['17','JUL','Python Programming','Internal Assessment','9:00 AM'],['21','JUL','Mobile App Development','Practical','12:00 PM'],['25','JUL','Information Security','Unit Test','8:00 AM']]
    },
    6: {
      notes: [
        ['PDF','Verified','Artificial Intelligence Search Algorithms','CIT321 • 332 views'],
        ['DOC','New','Cloud Computing Service Models','CIT323 • 194 views'],
        ['PDF','Verified','Data Mining Techniques','CIT325 • 241 views'],
        ['PDF','Popular','Human Computer Interaction Principles','CIT327 • 213 views'],
        ['LAB','Verified','Project Management Templates','CIT329 • 146 views'],
        ['PDF','Saved','Elective II Study Pack','ELE321 • 79 views']
      ],
      exams: [['16','JUL','Artificial Intelligence','Internal Assessment','10:00 AM'],['20','JUL','Cloud Computing','Presentation','1:00 PM'],['24','JUL','Data Mining','Unit Test','8:00 AM']]
    },
    7: {
      notes: [
        ['PDF','Verified','Machine Learning Algorithms','CIT411 • 346 views'],
        ['DOC','New','Internet of Things Architecture','CIT413 • 203 views'],
        ['PDF','Verified','Enterprise Application Patterns','CIT415 • 229 views'],
        ['PDF','Popular','Cyber Law and Ethics Notes','CIT417 • 278 views'],
        ['LAB','Verified','Major Project I Documentation Guide','PRJ411 • 184 views'],
        ['PDF','Saved','Elective III Study Pack','ELE411 • 75 views']
      ],
      exams: [['15','JUL','Machine Learning','Internal Assessment','9:00 AM'],['19','JUL','IoT','Project Demo','12:00 PM'],['23','JUL','Cyber Law','Unit Test','8:00 AM']]
    },
    8: {
      notes: [
        ['PDF','Verified','Big Data Analytics Notes','CIT421 • 318 views'],
        ['DOC','New','IT Governance Frameworks','CIT423 • 187 views'],
        ['PDF','Verified','Internship Report Format','INT421 • 269 views'],
        ['LAB','Popular','Major Project II Documentation','PRJ421 • 306 views'],
        ['PDF','Verified','Seminar Presentation Guide','SEM421 • 141 views'],
        ['PDF','Saved','Elective IV Study Pack','ELE421 • 73 views']
      ],
      exams: [['14','JUL','Big Data Analytics','Internal Assessment','10:00 AM'],['18','JUL','Major Project II','Final Demo','11:00 AM'],['22','JUL','IT Governance','Unit Test','8:00 AM']]
    }
  };

  document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = user.name);
  document.querySelectorAll('[data-user-firstname]').forEach(el => el.textContent = firstName);
  document.querySelectorAll('[data-user-email]').forEach(el => el.textContent = user.email);
  document.querySelectorAll('[data-user-semester]').forEach(el => el.textContent = user.semester);
  document.querySelectorAll('[data-user-initials]').forEach(el => el.textContent = initials);
  document.querySelectorAll('[data-my-notes]').forEach(link => link.href = `semester${semesterNumber}.html`);
  document.querySelectorAll('[data-dashboard-semester]').forEach(el => el.textContent = `Semester ${roman}`);

  const notesContainer = document.querySelector('#dashboardNotes');
  if (notesContainer) {
    notesContainer.innerHTML = semesterContent[semesterNumber].notes.map(([file, badge, title, meta]) => `
      <article class="note-card">
        <div class="note-cover"><div class="file-icon">${file}</div><span class="badge ${badge === 'Verified' ? 'badge-green' : 'badge-blue'}">${badge}</span></div>
        <div class="note-body"><h3>${title}</h3><div class="note-meta"><span>${meta}</span><span>• ${user.semester}</span></div></div>
      </article>`).join('');
  }

  const examContainer = document.querySelector('#dashboardExams');
  if (examContainer) {
    examContainer.innerHTML = semesterContent[semesterNumber].exams.map(([day, month, subject, type, time]) => `
      <div class="exam-item"><div class="exam-date">${day}<small>${month}</small></div><div><strong>${subject}</strong><div class="muted" style="font-size:.78rem">${type}</div></div><strong>${time}</strong></div>`).join('');
  }

  const sidebar = document.querySelector('.sidebar');
  const toggle = document.querySelector('.mobile-side-toggle');
  if (sidebar && toggle) toggle.addEventListener('click', () => sidebar.classList.toggle('open'));

  const openModalButtons = document.querySelectorAll('[data-open-upload]');
  const modal = document.querySelector('#uploadModal');
  const closeModal = document.querySelector('[data-close-modal]');
  if (modal) openModalButtons.forEach(button => button.addEventListener('click', event => {
    event.preventDefault();
    modal.classList.add('open');
  }));
  if (closeModal && modal) closeModal.addEventListener('click', () => modal.classList.remove('open'));
  if (modal) modal.addEventListener('click', event => { if (event.target === modal) modal.classList.remove('open'); });

  const uploadSemester = document.querySelector('#uploadSemester');
  if (uploadSemester) {
    uploadSemester.innerHTML = `<option value="${user.semester}">${user.semester}</option>`;
  }

  const uploadForm = document.querySelector('#uploadForm');
  if (uploadForm) {
    uploadForm.addEventListener('submit', event => {
      event.preventDefault();
      const uploads = Number(localStorage.getItem('gyankunjaUploads') || 18) + 1;
      localStorage.setItem('gyankunjaUploads', uploads);
      document.querySelectorAll('[data-upload-count]').forEach(el => el.textContent = uploads);
      uploadForm.reset();
      if (uploadSemester) uploadSemester.innerHTML = `<option value="${user.semester}">${user.semester}</option>`;
      modal.classList.remove('open');
      Gyankunja.toast(`Note saved under ${user.semester}. Backend upload can be connected later.`);
    });
  }
  document.querySelectorAll('[data-upload-count]').forEach(el => el.textContent = localStorage.getItem('gyankunjaUploads') || '18');

  const searchInput = document.querySelector('#dashboardSearch');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const term = searchInput.value.toLowerCase();
      document.querySelectorAll('.dashboard-note-grid .note-card').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    });
  }
})();
