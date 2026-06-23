import re

file = 'c:/PMS/Production_Plan/resources/js/Components/AutoSchedulerPopup.jsx'

with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

target = """                        setEvents(data.events);
                        setSumBatchByStage(data.sumBatchByStage);
                        if(data.plan) setPlan(data.plan);"""
replacement = """                        if (data.events) {
                            setEvents(data.events);
                            setSumBatchByStage(data.sumBatchByStage);
                            if(data.plan) setPlan(data.plan);
                        } else {
                            window.location.reload();
                        }"""

content = content.replace(target, replacement)

target2 = """                  setEvents(data.events);
                  setSumBatchByStage(data.sumBatchByStage);
                  if(data.plan) setPlan(data.plan);"""
replacement2 = """                  if (data.events) {
                      setEvents(data.events);
                      setSumBatchByStage(data.sumBatchByStage);
                      if(data.plan) setPlan(data.plan);
                  } else {
                      window.location.reload();
                  }"""
                  
content = content.replace(target2, replacement2)

target3 = """                            setEvents(data2.events);
                            setSumBatchByStage(data2.sumBatchByStage);
                            if(data2.plan) setPlan(data2.plan);"""
replacement3 = """                            if (data2.events) {
                                setEvents(data2.events);
                                setSumBatchByStage(data2.sumBatchByStage);
                                if(data2.plan) setPlan(data2.plan);
                            } else {
                                window.location.reload();
                            }"""

content = content.replace(target3, replacement3)

with open(file, 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated AutoSchedulerPopup.jsx to fallback to reload")
