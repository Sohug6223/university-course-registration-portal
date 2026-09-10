<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [

            /* CSE - 1st Semester */

            [
                'course_code' => 'BNG 1101',
                'course_name' => 'Bangla Language and Literature',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 2,
            ],
            [
                'course_code' => 'CHE 1105',
                'course_name' => 'Inorganic and Physical Chemistry',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 1111',
                'course_name' => 'Introduction to Computer System',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 2,
            ],
            [
                'course_code' => 'CSE 1112',
                'course_name' => 'Computer Fundamentals & Programming Laboratory',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 1,
            ],
            [
                'course_code' => 'ENG 1101',
                'course_name' => 'GEC-1: English',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 2,
            ],
            [
                'course_code' => 'HIST 2105',
                'course_name' => 'Bangladesh Studies (History of the Emergence of Bangladesh)',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 2,
            ],
            [
                'course_code' => 'MATH1101',
                'course_name' => 'Differential & Integral Calculas and Analytical Geometry',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 3,
            ],
            [
                'course_code' => 'PHY 1101',
                'course_name' => 'Physics I',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 3,
            ],
            [
                'course_code' => 'PHY 1102',
                'course_name' => 'Physics Laboratory',
                'department' => 'CSE',
                'semester' => '1st',
                'credit' => 1,
            ],


            /* CSE - 2nd Semester */

            [
                'course_code' => 'CSE 1213',
                'course_name' => 'Structured Programming Language',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 1214',
                'course_name' => 'Structured Programming Language Laboratory',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'EEE 1201',
                'course_name' => 'Electrical Engineering',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 3,
            ],
            [
                'course_code' => 'EEE 1202',
                'course_name' => 'Electrical Circuit Laboratory',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'ENG 1201',
                'course_name' => 'GEC-3: Freshman English - II',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 2,
            ],
            [
                'course_code' => 'EVS 1203',
                'course_name' => 'GEC-4: Environmental Science',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 2,
            ],
            [
                'course_code' => 'MATH 1203',
                'course_name' => 'Differential Equation & Complex Analysis',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 3,
            ],
            [
                'course_code' => 'PHY 1203',
                'course_name' => 'Physics II',
                'department' => 'CSE',
                'semester' => '2nd',
                'credit' => 3,
            ],


            /* CSE - 3rd Semester */

            [
                'course_code' => 'BUS 1205',
                'course_name' => 'Business Technology Entrepreneurship',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 2,
            ],
            [
                'course_code' => 'CE 2104',
                'course_name' => 'Engineering Drawing Laboratory',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 2111',
                'course_name' => 'Digital Logic Design',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 2112',
                'course_name' => 'Digital Logic Design Laboratory',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 2115',
                'course_name' => 'Object Oriented Programming',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 2116',
                'course_name' => 'Object Oriented Programming Laboratory',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 2117',
                'course_name' => 'Discrete Mathematics',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 3,
            ],
            [
                'course_code' => 'EPE 2101',
                'course_name' => 'Engineering Professional Ethics',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 2,
            ],
            [
                'course_code' => 'MATH 2105',
                'course_name' => 'Linear Algebra, Laplace Transformation and Fourier analysis',
                'department' => 'CSE',
                'semester' => '3rd',
                'credit' => 3,
            ],


            /* CSE - 4th Semester */

            [
                'course_code' => 'CSE 2200',
                'course_name' => 'Software Development Project I',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 0.75,
            ],
            [
                'course_code' => 'CSE 2213',
                'course_name' => 'Data Structure',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 2214',
                'course_name' => 'Data Structure Laboratory',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 2215',
                'course_name' => 'Numerical Analysis',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 2216',
                'course_name' => 'Numerical Analysis Laboratory',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 2217',
                'course_name' => 'Data Communication',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 3,
            ],
            [
                'course_code' => 'EEE 2203',
                'course_name' => 'Electronic Circuit -I',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 3,
            ],
            [
                'course_code' => 'EEE 2204',
                'course_name' => 'Electronic Circuit Laboratory',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'MATH 2207',
                'course_name' => 'Statistics and Probability',
                'department' => 'CSE',
                'semester' => '4th',
                'credit' => 3,
            ],


            /* CSE - 5th Semester */

            [
                'course_code' => 'CSE 3111',
                'course_name' => 'Database Management System',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3112',
                'course_name' => 'Database Management System Laboratory',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 3115',
                'course_name' => 'Algorithm',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3116',
                'course_name' => 'Algorithm Laboratory',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 3117',
                'course_name' => 'Operating System',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3118',
                'course_name' => 'Operating System Lab',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 3119',
                'course_name' => 'Theory of Computing',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3210',
                'course_name' => 'Computer Graphics Laboratory',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 1,
            ],
            [
                'course_code' => 'ME 3101',
                'course_name' => 'Basic Mechanical Engineering',
                'department' => 'CSE',
                'semester' => '5th',
                'credit' => 3,
            ],


            /* CSE - 6th Semester */

            [
                'course_code' => 'CSE 3200',
                'course_name' => 'Software Development Project II',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 0.75,
            ],
            [
                'course_code' => 'CSE 3211',
                'course_name' => 'Microprocessors, Microcontrollers and Assembly Language',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3212',
                'course_name' => 'Microprocessors, Microcontrollers and Assembly Language Laboratory',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 3213',
                'course_name' => 'Computer Architecture',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3215',
                'course_name' => 'System Analysis & Design',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3216',
                'course_name' => 'System Analysis & Design Laboratory',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 3217',
                'course_name' => 'Computer Networks and Security',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 3218',
                'course_name' => 'Computer Networks and Security Laboratory',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 3219',
                'course_name' => 'Compiler Design',
                'department' => 'CSE',
                'semester' => '6th',
                'credit' => 3,
            ],


            /* CSE - 7th Semester */

            [
                'course_code' => 'CSE 4000 (Part-I)',
                'course_name' => 'Thesis/Project',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4111',
                'course_name' => 'Digital System Design',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4112',
                'course_name' => 'Digital System Design Laboratory',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 4115',
                'course_name' => 'Software Engineering',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4116',
                'course_name' => 'Software Engineering Laboratory',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 4117',
                'course_name' => 'Artificial Intelligence',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4118',
                'course_name' => 'Artificial Intelligence Laboratory',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 4141',
                'course_name' => 'Computer Simulation & Modeling',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4143',
                'course_name' => 'Software Quality Assurance',
                'department' => 'CSE',
                'semester' => '7th',
                'credit' => 3,
            ],


            /* CSE - 8th Semester */

            [
                'course_code' => 'CSE 4200',
                'course_name' => 'Thesis/Project Part-II',
                'department' => 'CSE',
                'semester' => '8th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4211',
                'course_name' => 'Advanced Software Engineering',
                'department' => 'CSE',
                'semester' => '8th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4212',
                'course_name' => 'Software Engineering Laboratory',
                'department' => 'CSE',
                'semester' => '8th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 4213',
                'course_name' => 'Machine Learning',
                'department' => 'CSE',
                'semester' => '8th',
                'credit' => 3,
            ],
            [
                'course_code' => 'CSE 4214',
                'course_name' => 'Machine Learning Laboratory',
                'department' => 'CSE',
                'semester' => '8th',
                'credit' => 1.5,
            ],
            [
                'course_code' => 'CSE 4215',
                'course_name' => 'Professional Practice & Ethics',
                'department' => 'CSE',
                'semester' => '8th',
                'credit' => 2,
            ],
            [
                'course_code' => 'CSE 4216',
                'course_name' => 'Technical Seminar',
                'department' => 'CSE',
                'semester' => '8th',
                'credit' => 1,
            ],


            /* EEE - 1st Semester */

            [
                'course_code' => 'EEE101',
                'course_name' => 'Electrical Circuit I',
                'department' => 'EEE',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE102',
                'course_name' => 'Electrical Circuit I Lab',
                'department' => 'EEE',
                'semester' => '1st',
                'credit' => 1.0,
            ],
            [
                'course_code' => 'EEE103',
                'course_name' => 'Engineering Mathematics I',
                'department' => 'EEE',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE104',
                'course_name' => 'Physics I',
                'department' => 'EEE',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE105',
                'course_name' => 'Basic Electrical Engineering',
                'department' => 'EEE',
                'semester' => '1st',
                'credit' => 3.0,
            ],


            /* EEE - 2nd Semester */

            [
                'course_code' => 'EEE201',
                'course_name' => 'Electrical Circuit II',
                'department' => 'EEE',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE202',
                'course_name' => 'Electrical Circuit II Lab',
                'department' => 'EEE',
                'semester' => '2nd',
                'credit' => 1.0,
            ],
            [
                'course_code' => 'EEE203',
                'course_name' => 'Engineering Mathematics II',
                'department' => 'EEE',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE204',
                'course_name' => 'Electronic Devices',
                'department' => 'EEE',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE205',
                'course_name' => 'Electronic Devices Lab',
                'department' => 'EEE',
                'semester' => '2nd',
                'credit' => 1.0,
            ],


            /* EEE - 3rd Semester */

            [
                'course_code' => 'EEE301',
                'course_name' => 'Analog Electronics',
                'department' => 'EEE',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE302',
                'course_name' => 'Analog Electronics Lab',
                'department' => 'EEE',
                'semester' => '3rd',
                'credit' => 1.0,
            ],
            [
                'course_code' => 'EEE303',
                'course_name' => 'Electromagnetic Fields',
                'department' => 'EEE',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE304',
                'course_name' => 'Signals and Systems',
                'department' => 'EEE',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE305',
                'course_name' => 'Electrical Machines I',
                'department' => 'EEE',
                'semester' => '3rd',
                'credit' => 3.0,
            ],


            /* EEE - 4th Semester */

            [
                'course_code' => 'EEE401',
                'course_name' => 'Digital Electronics',
                'department' => 'EEE',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE402',
                'course_name' => 'Digital Electronics Lab',
                'department' => 'EEE',
                'semester' => '4th',
                'credit' => 1.0,
            ],
            [
                'course_code' => 'EEE403',
                'course_name' => 'Electrical Machines II',
                'department' => 'EEE',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE404',
                'course_name' => 'Power Systems I',
                'department' => 'EEE',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE405',
                'course_name' => 'Control Systems I',
                'department' => 'EEE',
                'semester' => '4th',
                'credit' => 3.0,
            ],


            /* EEE - 5th Semester */

            [
                'course_code' => 'EEE501',
                'course_name' => 'Microprocessor and Microcontroller',
                'department' => 'EEE',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE502',
                'course_name' => 'Microprocessor Lab',
                'department' => 'EEE',
                'semester' => '5th',
                'credit' => 1.0,
            ],
            [
                'course_code' => 'EEE503',
                'course_name' => 'Power Electronics',
                'department' => 'EEE',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE504',
                'course_name' => 'Communication Engineering',
                'department' => 'EEE',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE505',
                'course_name' => 'Control Systems II',
                'department' => 'EEE',
                'semester' => '5th',
                'credit' => 3.0,
            ],


            /* EEE - 6th Semester */

            [
                'course_code' => 'EEE601',
                'course_name' => 'Digital Signal Processing',
                'department' => 'EEE',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE602',
                'course_name' => 'DSP Lab',
                'department' => 'EEE',
                'semester' => '6th',
                'credit' => 1.0,
            ],
            [
                'course_code' => 'EEE603',
                'course_name' => 'Power Systems II',
                'department' => 'EEE',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE604',
                'course_name' => 'VLSI Design',
                'department' => 'EEE',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE605',
                'course_name' => 'Renewable Energy Systems',
                'department' => 'EEE',
                'semester' => '6th',
                'credit' => 3.0,
            ],


            /* EEE - 7th Semester */

            [
                'course_code' => 'EEE701',
                'course_name' => 'Power System Protection',
                'department' => 'EEE',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE702',
                'course_name' => 'Industrial Electronics',
                'department' => 'EEE',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE703',
                'course_name' => 'Embedded Systems',
                'department' => 'EEE',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE704',
                'course_name' => 'Embedded Systems Lab',
                'department' => 'EEE',
                'semester' => '7th',
                'credit' => 1.0,
            ],
            [
                'course_code' => 'EEE705',
                'course_name' => 'Project I',
                'department' => 'EEE',
                'semester' => '7th',
                'credit' => 1.5,
            ],


            /* EEE - 8th Semester */

            [
                'course_code' => 'EEE801',
                'course_name' => 'Advanced Power Systems',
                'department' => 'EEE',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE802',
                'course_name' => 'Smart Grid Technology',
                'department' => 'EEE',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE803',
                'course_name' => 'Wireless Communication',
                'department' => 'EEE',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE804',
                'course_name' => 'Project II',
                'department' => 'EEE',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'EEE805',
                'course_name' => 'Professional Ethics',
                'department' => 'EEE',
                'semester' => '8th',
                'credit' => 2.0,
            ],


            /* BBA - 1st Semester */

            [
                'course_code' => 'BBA101',
                'course_name' => 'Principles of Management',
                'department' => 'BBA',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA102',
                'course_name' => 'Principles of Marketing',
                'department' => 'BBA',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA103',
                'course_name' => 'Business Mathematics',
                'department' => 'BBA',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA104',
                'course_name' => 'Microeconomics',
                'department' => 'BBA',
                'semester' => '1st',
                'credit' => 3.0,
            ],


            /* BBA - 2nd Semester */

            [
                'course_code' => 'BBA201',
                'course_name' => 'Financial Accounting',
                'department' => 'BBA',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA202',
                'course_name' => 'Business Statistics',
                'department' => 'BBA',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA203',
                'course_name' => 'Macroeconomics',
                'department' => 'BBA',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA204',
                'course_name' => 'Organizational Behavior',
                'department' => 'BBA',
                'semester' => '2nd',
                'credit' => 3.0,
            ],


            /* BBA - 3rd Semester */

            [
                'course_code' => 'BBA301',
                'course_name' => 'Human Resource Management',
                'department' => 'BBA',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA302',
                'course_name' => 'Business Communication',
                'department' => 'BBA',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA303',
                'course_name' => 'Cost Accounting',
                'department' => 'BBA',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA304',
                'course_name' => 'Business Law',
                'department' => 'BBA',
                'semester' => '3rd',
                'credit' => 3.0,
            ],


            /* BBA - 4th Semester */

            [
                'course_code' => 'BBA401',
                'course_name' => 'Financial Management',
                'department' => 'BBA',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA402',
                'course_name' => 'Operations Management',
                'department' => 'BBA',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA403',
                'course_name' => 'Management Information Systems',
                'department' => 'BBA',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA404',
                'course_name' => 'Entrepreneurship',
                'department' => 'BBA',
                'semester' => '4th',
                'credit' => 3.0,
            ],


            /* BBA - 5th Semester */

            [
                'course_code' => 'BBA501',
                'course_name' => 'Consumer Behavior',
                'department' => 'BBA',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA502',
                'course_name' => 'International Business',
                'department' => 'BBA',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA503',
                'course_name' => 'Taxation',
                'department' => 'BBA',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA504',
                'course_name' => 'Management Accounting',
                'department' => 'BBA',
                'semester' => '5th',
                'credit' => 3.0,
            ],


            /* BBA - 6th Semester */

            [
                'course_code' => 'BBA601',
                'course_name' => 'Strategic Management',
                'department' => 'BBA',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA602',
                'course_name' => 'Investment Management',
                'department' => 'BBA',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA603',
                'course_name' => 'Supply Chain Management',
                'department' => 'BBA',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA604',
                'course_name' => 'Banking and Insurance',
                'department' => 'BBA',
                'semester' => '6th',
                'credit' => 3.0,
            ],


            /* BBA - 7th Semester */

            [
                'course_code' => 'BBA701',
                'course_name' => 'Corporate Finance',
                'department' => 'BBA',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA702',
                'course_name' => 'Digital Marketing',
                'department' => 'BBA',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA703',
                'course_name' => 'Business Analytics',
                'department' => 'BBA',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA704',
                'course_name' => 'Project I',
                'department' => 'BBA',
                'semester' => '7th',
                'credit' => 1.5,
            ],


            /* BBA - 8th Semester */

            [
                'course_code' => 'BBA801',
                'course_name' => 'Strategic Human Resource Management',
                'department' => 'BBA',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA802',
                'course_name' => 'Advanced Marketing',
                'department' => 'BBA',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA803',
                'course_name' => 'Business Research Methods',
                'department' => 'BBA',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'BBA804',
                'course_name' => 'Project II',
                'department' => 'BBA',
                'semester' => '8th',
                'credit' => 3.0,
            ],


            /* English - 1st Semester */

            [
                'course_code' => 'ENG101',
                'course_name' => 'Introduction to Literature',
                'department' => 'English',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG102',
                'course_name' => 'Academic Reading and Writing',
                'department' => 'English',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG103',
                'course_name' => 'Introduction to Linguistics',
                'department' => 'English',
                'semester' => '1st',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG104',
                'course_name' => 'English Grammar',
                'department' => 'English',
                'semester' => '1st',
                'credit' => 3.0,
            ],


            /* English - 2nd Semester */

            [
                'course_code' => 'ENG201',
                'course_name' => 'Poetry',
                'department' => 'English',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG202',
                'course_name' => 'Prose',
                'department' => 'English',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG203',
                'course_name' => 'Phonetics and Phonology',
                'department' => 'English',
                'semester' => '2nd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG204',
                'course_name' => 'Creative Writing',
                'department' => 'English',
                'semester' => '2nd',
                'credit' => 3.0,
            ],


            /* English - 3rd Semester */

            [
                'course_code' => 'ENG301',
                'course_name' => 'Drama',
                'department' => 'English',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG302',
                'course_name' => 'Short Stories',
                'department' => 'English',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG303',
                'course_name' => 'Morphology and Syntax',
                'department' => 'English',
                'semester' => '3rd',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG304',
                'course_name' => 'Critical Reading',
                'department' => 'English',
                'semester' => '3rd',
                'credit' => 3.0,
            ],


            /* English - 4th Semester */

            [
                'course_code' => 'ENG401',
                'course_name' => 'English Renaissance Literature',
                'department' => 'English',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG402',
                'course_name' => 'Romantic Literature',
                'department' => 'English',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG403',
                'course_name' => 'Victorian Literature',
                'department' => 'English',
                'semester' => '4th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG404',
                'course_name' => 'Literary Criticism',
                'department' => 'English',
                'semester' => '4th',
                'credit' => 3.0,
            ],


            /* English - 5th Semester */

            [
                'course_code' => 'ENG501',
                'course_name' => 'Modern Poetry',
                'department' => 'English',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG502',
                'course_name' => 'Modern Drama',
                'department' => 'English',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG503',
                'course_name' => 'American Literature',
                'department' => 'English',
                'semester' => '5th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG504',
                'course_name' => 'Sociolinguistics',
                'department' => 'English',
                'semester' => '5th',
                'credit' => 3.0,
            ],


            /* English - 6th Semester */

            [
                'course_code' => 'ENG601',
                'course_name' => 'Postcolonial Literature',
                'department' => 'English',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG602',
                'course_name' => 'World Literature',
                'department' => 'English',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG603',
                'course_name' => 'Applied Linguistics',
                'department' => 'English',
                'semester' => '6th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG604',
                'course_name' => 'Translation Studies',
                'department' => 'English',
                'semester' => '6th',
                'credit' => 3.0,
            ],


            /* English - 7th Semester */

            [
                'course_code' => 'ENG701',
                'course_name' => 'Research Methodology',
                'department' => 'English',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG702',
                'course_name' => 'Gender and Literature',
                'department' => 'English',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG703',
                'course_name' => 'Language Teaching Methods',
                'department' => 'English',
                'semester' => '7th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG704',
                'course_name' => 'Project I',
                'department' => 'English',
                'semester' => '7th',
                'credit' => 1.5,
            ],


            /* English - 8th Semester */

            [
                'course_code' => 'ENG801',
                'course_name' => 'Contemporary Literature',
                'department' => 'English',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG802',
                'course_name' => 'Advanced Literary Theory',
                'department' => 'English',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG803',
                'course_name' => 'Dissertation / Project',
                'department' => 'English',
                'semester' => '8th',
                'credit' => 3.0,
            ],
            [
                'course_code' => 'ENG804',
                'course_name' => 'Professional Communication',
                'department' => 'English',
                'semester' => '8th',
                'credit' => 2.0,
            ],
        ];


        /* Insert / Update Courses */

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['course_code' => $course['course_code']],
                [
                    'course_name' => $course['course_name'],
                    'department_id' => \App\Models\Department::where('code', $course['department'])->value('id'),
                    'semester' => $course['semester'],
                    'credit' => $course['credit'],

                    // Theory / Lab automatically detect
                    'course_type' => (
                        str_contains(strtolower($course['course_name']), 'lab') ||
                        str_contains(strtolower($course['course_name']), 'laboratory')
                    ) ? 'Lab' : 'Theory',

                    'description' => $course['course_name'],
                    'status' => 'Active',
                ]
            );
        }
    }
}
