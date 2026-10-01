# 🏫 BSLA / SIA High School Portal - Setup Guide para sa Kaklase

Magandang araw! Narito ang mabilis at simpleng 3-step guide para mapagana ang buong system sa kahit anong PC gamit ang **XAMPP**.

---

## 🚀 3-Step Mabilisang Pag-Setup

### Step 1: Ilagay ang Folder sa XAMPP
Ilagay o i-paste ang buong folder na **`sia-project2`** sa iyong XAMPP `htdocs` directory:
```text
C:\xampp\htdocs\sia-project2
```

### Step 2: Buksan ang XAMPP at I-import ang Database
1. Buksan ang **XAMPP Control Panel** at i-click ang **Start** sa **Apache** at **MySQL**.
2. Buksan ang iyong browser at pumunta sa **phpMyAdmin**:
   👉 `http://localhost/phpmyadmin/`
3. I-click ang tab na **"Import"** sa itaas na menu.
4. I-click ang **"Choose File"** (Pumili ng File) at piliin ang:
   👉 `C:\xampp\htdocs\sia-project2\sia_highschool_complete_database.sql`
5. I-scroll pababa at i-click ang **"Import"** / **"Go"**.
   *(Awtomatiko na nitong bubuuin ang database na `sia_highschool_db` kasama ang lahat ng tables, 104 DepEd subjects, 33 sections, 1,680 schedules, at 31 school events).*

### Step 3: Buksan ang System sa Browser!
I-type ang URL na ito sa iyong browser (Google Chrome / Edge):
👉 **`http://localhost/sia-project2/frontend/dist/`**

*(O kung nais mag-edit gamit ang Vue/Vite dev server, mag-`cd frontend` at mag-`npm run dev`).*

---

## 🔑 Mga Verified Demo Accounts na Pwedeng I-login

Lahat ng demo accounts ay may default password na: **`password123`**

| Portal / Role | Username | Password | Deskripsyon / Gawain |
| :--- | :--- | :--- | :--- |
| 🛡️ **Super Administrator** | `admin` | `password123` | System audit trail, user controls, security logs |
| 📚 **Academic Coordinator** | `maria_coordinator` | `password123` | Curriculum, DepEd Subjects, Timetables, School Events |
| 📝 **Registrar & Admissions** | `maria_registrar` | `password123` | Student credentials, application reviews, queue |
| 💳 **Treasury / Cashier** | `maria_treasury` | `password123` | Tuition fees assessment, payment receipts, vouchers |
| 📁 **School Records** | `maria_records` | `password123` | DepEd Form 137/138, SF9/SF10, document requests |
| 👨‍🏫 **Faculty / Teacher (JHS)** | `prof_delacruz` | `password123` | Math & Calculus Teacher schedule, LMS classroom & grading |
| 👨‍🏫 **Faculty / Teacher (SHS)** | `prof_santos` | `password123` | Science & Physics Teacher schedule, LMS classroom & grading |
| 👨‍🏫 **Faculty / Teacher** | `prof_tan` | `password123` | English & EAPP Teacher schedule / loading |
| 👨‍🏫 **Faculty / Teacher** | `prof_turing` | `password123` | ICT & Computer Systems Teacher schedule / loading |
| 🎓 **Senior High Student** | `2026-SHS-0005` | `password123` | Student Dashboard, Statement of Account, Class Sched, LMS |
| 🎓 **Junior High Student** | `2026-JHS-0001` | `password123` | JHS Student Portal, Timetable, School Events, LMS |
| 📋 **Admission Applicant** | `test_applicant` | `password123` | Online admission procedure, requirements upload |

---

## 🌟 Mga Tampok na Features ng System
1. **Public Registration**: May live student demographics input, password validation, at voucher category conditionality para sa SHS.
2. **DepEd Curriculum**: May opisyal na subjects (JHS Grades 7–10 at SHS Grades 11–12 across STEM, ABM, HUMSS, GAS, TVL-ICT, TVL-HE) na may prerequisites.
3. **Conflict-Free Timetable Manager**: May 1,680 class periods sa 33 sections na may 0 teacher overlap, 0 section collision, at 0 room collision.
4. **School Events Calendar**: May opisyal na academic at institutional events para sa S.Y. 2026–2027.
5. **Modern Accordion Sidenav**: May makinis na sliding CSS Grid animation para sa Admin & Coordinator.
6. **Learning Management System (LMS) - Pinakabagong Update**:
   - **Graded Submission Lock**: Kapag na-grade-an na ng guro ang assignment ng estudyante, hindi na ito mababago o ma-eedit (403 protected).
   - **Past-Deadline Warnings**: Awtomatikong nagpapakita ng warning badge kapag lumipas na ang deadline ng submission, may `isLate` tagging sa guro.
   - **Student Real-Time Stream Feed**: Agad na nakikita ng estudyante ang bagong upload na modules, announcements, at assignments ng kanilang mga guro.
   - **Cross-Semester Protection**: Hindi pwedeng mag-upload ang guro sa mga nakakandadong (locked) school years o semesters.
   - **Modern Modal Dialogs**: Lahat ng native browser popups (`alert` / `confirm`) ay napalitan ng modern, animated UI modals.
