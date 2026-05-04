type Props = {
  siteName: string;
};

export function DabaoFooter({ siteName }: Props) {
  return (
    <>
      <div className="footer clearfix">
        <p>
          免责声明:本站所有视频均来自互联网收集而来，版权归原创者所有，如果侵犯了你的权益，请通知我们，我们会及时删除侵权内容，谢谢合作。
        </p>
        <p>
          Copyright © {new Date().getFullYear()} 《{siteName}》
        </p>
        <p>
          <a>版权投诉邮箱:26995787@qq.com</a>
        </p>
      </div>
      <div className="gotop">
        <a
          className="gotopbg"
          href="javascript:;"
          title="返回顶部"
          onClick={(e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }}
        />
      </div>
    </>
  );
}
