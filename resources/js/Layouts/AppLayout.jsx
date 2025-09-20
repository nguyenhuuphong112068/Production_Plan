import React from 'react';

export default function AppLayout({ children, user, title }) {
  return (
    <div className="min-h-screen flex flex-col">


      <header className="bg-white fixed top-0 z-10 w-[100%] border">
        <div className="flex mx-auto items-center justify-between px-4 py-2">
          
          {/* Cột 1: Logo */}
          <div className="w-1/3 ml-[50px] flex justify-start items-center">
            <a href="/home" className="flex items-center">
              <img
                src="/img/iconstella.svg"
                alt="Logo"
                style={{ opacity: 0.8, maxWidth: '43px', height: 'auto' }}
              />
            </a>
          </div>

        {/* Cột 2: Tiêu đề */}
          <div className="w-1/3 text-center">
            <h4 className="text-xl font-semibold text-yellow-600" style={{color: "#CDC717"}}>{title}</h4>
            </div>
            {/* Cột 3: User Info */}
            <div className="w-1/3 mr-[50px] flex justify-end text-right">
              {user && (
                <div>
                  <div>🧑‍💼 {user.fullName}</div>
                  <div>🛡️ {user.userGroup}</div>
                </div>
              )}
            </div>
          </div>
        </header>

      
            {/* TOP NAV */}
      <div className="flex flex-1 flex-col">
          {/* <LeftNAV /> */}
          <main className="pt-[2%] bg-white-100 min-h-screen">
            {children}
          </main>
      </div> 


      

    </div>
  );
}
