import sys
import pandas as pd
import mysql.connector
from io import StringIO

_HOST = 'localhost'
_USER = 'moodleuser'
_DB = 'moodle'

global conn
conn = None
global cur
cur = None

def _open_db(database=_DB):
    global conn
    global cur
    conn = mysql.connector.connect(host=_HOST, user=_USER, passwd=_PASSWD, db=database)
    cur = conn.cursor()
    return

def _close_db():
    global conn
    global cur
    conn.commit()
    cur.close()
    conn.close()
    return

def select_data(biometric):
    
    _open_db()
    cur.execute("SELECT email,session,ipaddress,useragent,appversion,task,tags,csvdata FROM mdl_bioauth_biodata,mdl_user WHERE biometric='{}' and userid=mdl_user.id".format(biometric))
    rows = cur.fetchall()
    _close_db()
    
    dfs = []
    for email,session,ipaddress,useragent,appversion,task,tags,csvdata in rows:
        df = pd.read_csv(StringIO(csvdata))
        df['ipaddress'] = ipaddress
        df['useragent'] = useragent
        df['appversion'] = appversion
        df['task'] = task
        df['tags'] = tags
        df.index = pd.MultiIndex.from_tuples([(email,session)]*len(df))
        df.index.names = ['email','session']
        print('Loaded {} from {}'.format(session,email))
        dfs.append(df)
    
    dfs = pd.concat(dfs)
    dfs = dfs.sort_index()
    return dfs

if __name__ == '__main__':

    if len(sys.argv) < 2:
        print('No password provided')
        sys.exit(1)

    global _PASSWD
    _PASSWD = sys.argv[1]
    biometric = sys.argv[2]
    out = sys.argv[3]
    df = select_data(biometric)
    df.to_csv(out)
