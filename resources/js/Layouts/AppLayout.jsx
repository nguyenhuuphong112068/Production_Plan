import React from 'react';
import LeftNav from '../Components/leftNAV';

export default function AppLayout({ children, user, title }) {
  return (
    <div className="min-h-screen flex flex-col">
      {/* TOP NAV */}
      <div className="flex flex-1 flex-col">
          <main className="pt-[80px] bg-red-100 min-h-screen">
            {children}
           
          </main>
      </div> 

      <header className="bg-white fixed  top-0 z-10 w-[195px] ">
        <div className="flex mx-auto items-center justify-between px-4 py-2">
          
          {/* Cột 1: Logo */}
          <div className=" ml-[50px] flex justify-start items-center">
            <a href="/Schedual" className="flex items-center">
              <img
                src="/img/iconstella.svg"
                alt="Logo"
                style={{ opacity: 0.8, maxWidth: '43px', height: 'auto' }}
              />
            </a>
          </div>

         

        </div>
      </header>

      {/* Cột 2: Tiêu đề */}
          {/* <div className="w-1/3 text-center">
            <h4 className="text-lg font-semibold text-yellow-600">{title}</h4>
          </div> */}

          {/* Cột 3: User Info */}
          {/* <div className="w-1/3 mr-[50px] flex justify-end text-right">
            {user && (
              <div>
                <div>🧑‍💼 {user.fullName}</div>
                <div>🛡️ {user.userGroup}</div>
              </div>
            )}
          </div> */}

    </div>
  );
}
