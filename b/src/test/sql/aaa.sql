--��֯������Ȩ�����
/*��ջ���վ  
drop table nst_person
PURGE RECYCLEBIN 
*/

/*��֯������ÿ����˾���е�,�Ǽܹ�ҵ��ϵͳ�Ļ���
��֯��������,����������һ����׼������ÿ������ҵ��λ������һ�������,Ψһ�ı��
*/
create table nst_organization (
        guid      varchar(200)        primary key --����,ʹ�� SYS-ID,���Բ���һ��ȫ��Ψһ�ı��,��֪�����ݿ��ܷ����
       ,key       varchar(200)        unique      --��֯��������,�в㼶��ϵ�ı���,����������νṹ
       ,name      varchar(200)        not null    --��֯��������
       ,remark    varchar2(4000)                  --��֯��������,����һ�����,����Ҫ�洢 HTML �ַ���
)

/*��Ա��,����������ʵ�����д��ڵĸ��˵�λ
*/
create table nst_person(
        guid     varchar(200)         primary key --����,ʹ�� SYS-ID
       ,name     varchar(200)                     --����,��Щϵͳ������� first-name , last-name ��¼����������,���Դ���
       ,sex      int                              --�ο������ļ����Ա�Ķ���: 0 δ֪,1��,2Ů,9�����Ա�
       ,birthday date                             --����
)